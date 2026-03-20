<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\GroupAttendance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'patient')
            ->with(['patientGroups', 'attendances', 'weightRecords' => fn($q) => $q->latest('recorded_at')]);

        if ($search = $request->input('search')) {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
            );
        }

        $patients = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('coordinator.patients.index', compact('patients'));
    }

    public function show(User $patient)
    {
        $groups = $patient->patientGroups()->get();

        $weightRecords = $patient->weightRecords()
            ->with('group')
            ->latest('recorded_at')
            ->get();

        $attendances = $patient->attendances()
            ->with('group')
            ->latest('attended_at')
            ->get();

        $firstWeight = $weightRecords->last()?->weight;
        $lastWeight  = $weightRecords->first()?->weight;
        $totalChange = ($firstWeight && $lastWeight) ? round($lastWeight - $firstWeight, 2) : null;

        // Total minutes in groups (cast to int — Carbon 3 returns float from diffInMinutes)
        $attendedGroupIds = $attendances->pluck('group_id')->unique();
        $totalMinutes = (int) $groups->whereIn('id', $attendedGroupIds)->sum(function ($g) {
            if ($g->started_at && $g->ended_at)  return (int) $g->started_at->diffInMinutes($g->ended_at);
            if ($g->started_at && $g->active)     return (int) $g->started_at->diffInMinutes(now());
            return 0;
        });

        // Attendance rate: compare patient's visits vs unique session dates per group
        $totalSessions    = 0;
        $attendedSessions = 0;
        foreach ($groups as $g) {
            // Total sessions = distinct dates any patient attended this group
            $groupSessionDates = GroupAttendance::where('group_id', $g->id)
                ->selectRaw('DATE(attended_at) as d')
                ->distinct()
                ->pluck('d');
            $totalSessions += $groupSessionDates->count();

            // Patient's attended sessions = their attendance dates that match group session dates
            $patientDates = $attendances->where('group_id', $g->id)
                ->map(fn($a) => $a->attended_at->format('Y-m-d'))
                ->unique();
            $attendedSessions += $groupSessionDates->intersect($patientDates)->count();
        }
        $attendanceRate = $totalSessions > 0 ? round($attendedSessions / $totalSessions * 100) : null;

        // Linear regression trend (kg/sesión, negative = losing)
        $chartRecords = $weightRecords->sortBy('recorded_at')->values();
        $trend = $this->weightTrend($chartRecords);

        // Progress toward ideal weight
        $progressPct = null;
        if ($firstWeight && $patient->ideal_weight && (float)$firstWeight !== (float)$patient->ideal_weight) {
            $totalNeeded = (float)$firstWeight - (float)$patient->ideal_weight;
            $achieved    = (float)$firstWeight - (float)$lastWeight;
            $progressPct = $totalNeeded != 0 ? max(0, min(100, round($achieved / $totalNeeded * 100))) : null;
        }

        // In maintenance range?
        $piso  = $patient->peso_piso;
        $techo = $patient->peso_techo;
        $inRange = ($lastWeight && $piso && $techo)
            ? ((float)$lastWeight >= (float)$piso && (float)$lastWeight <= (float)$techo)
            : null;

        // Chart data
        $chartData = [
            'labels'  => $chartRecords->map(fn($r) => $r->recorded_at->format('d/m'))->toArray(),
            'weights' => $chartRecords->map(fn($r) => (float) $r->weight)->toArray(),
            'piso'    => $piso  ? (float) $piso  : null,
            'techo'   => $techo ? (float) $techo : null,
        ];

        // Timeline with weight change deltas
        $weightByAttendance = $weightRecords->keyBy(
            fn($w) => $w->group_id . '_' . $w->recorded_at->format('Y-m-d')
        );
        $timeline = $attendances->map(function ($att) use ($weightByAttendance) {
            $key = $att->group_id . '_' . $att->attended_at->format('Y-m-d');
            return [
                'date'       => $att->attended_at,
                'group_name' => $att->group?->name ?? '(Grupo eliminado)',
                'weight'     => $weightByAttendance->get($key)?->weight,
            ];
        });
        $timelineWithChange = $timeline->values()->map(function ($entry, $index) use ($timeline) {
            $next = $timeline->get($index + 1);
            $entry['change'] = ($entry['weight'] && $next && $next['weight'])
                ? round($entry['weight'] - $next['weight'], 2)
                : null;
            return $entry;
        });

        return view('coordinator.patients.show', compact(
            'patient', 'groups', 'weightRecords', 'attendances',
            'totalChange', 'firstWeight', 'lastWeight', 'totalMinutes',
            'timelineWithChange', 'trend', 'attendanceRate', 'attendedSessions',
            'totalSessions', 'progressPct', 'inRange', 'chartData'
        ));
    }

    public function aiAnalysis(User $patient): JsonResponse
    {
        $cacheKey = "ai_analysis_{$patient->id}_" . now()->format('Y-m-d');

        $analysis = Cache::remember($cacheKey, 3600 * 6, function () use ($patient) {
            $records  = $patient->weightRecords()->orderBy('recorded_at')->get();
            $firstW   = $records->first()?->weight ?? 'desconocido';
            $lastW    = $records->last()?->weight  ?? 'desconocido';
            $trend    = $this->weightTrend($records->values());
            $sessions = $records->count();
            $piso     = $patient->peso_piso  ?? 'no definido';
            $techo    = $patient->peso_techo ?? 'no definido';
            $ideal    = $patient->ideal_weight ?? 'no definido';

            $prompt = "Analizá los siguientes datos clínicos de un paciente de un grupo terapéutico de control de peso " .
                "y generá una devolución profesional breve (4-5 oraciones) en español para el coordinador del grupo. " .
                "Usá un tono clínico, empático y orientado a la acción.\n\n" .
                "Datos del paciente:\n" .
                "- Peso inicial: {$firstW} kg\n" .
                "- Peso actual: {$lastW} kg\n" .
                "- Tendencia: " . round($trend, 2) . " kg/sesión (negativo = pérdida)\n" .
                "- Sesiones asistidas: {$sessions}\n" .
                "- Peso ideal: {$ideal} kg\n" .
                "- Rango de mantenimiento: {$piso} – {$techo} kg\n\n" .
                "Incluí: interpretación de la tendencia, valoración de la adherencia, y una sugerencia clínica concreta.";

            $response = Http::withToken(config('services.groq.key'))
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'      => 'llama-3.3-70b-versatile',
                    'max_tokens' => 350,
                    'messages'   => [
                        ['role' => 'system', 'content' => 'Sos un psicólogo clínico especialista en grupos terapéuticos de control de peso. Respondés siempre en español con lenguaje profesional y empático.'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                ]);

            return $response->json('choices.0.message.content')
                ?? 'No se pudo generar el análisis. Intentá nuevamente.';
        });

        return response()->json(['analysis' => $analysis]);
    }

    private function weightTrend(Collection $records): float
    {
        $n = $records->count();
        if ($n < 2) return 0;
        $x = range(0, $n - 1);
        $y = $records->pluck('weight')->map(fn($w) => (float) $w)->toArray();
        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;
        $num = 0; $den = 0;
        foreach ($x as $i => $xi) {
            $num += ($xi - $meanX) * ($y[$i] - $meanY);
            $den += ($xi - $meanX) ** 2;
        }
        return $den > 0 ? round($num / $den, 3) : 0;
    }
}
