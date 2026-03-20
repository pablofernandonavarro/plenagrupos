<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\TherapeuticSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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

        // Attendance rate across all patient's groups
        $totalSessions  = 0;
        $attendedSessions = 0;
        foreach ($groups as $g) {
            $totalSessions    += TherapeuticSession::where('group_id', $g->id)->count();
            $attendedSessions += $attendances->where('group_id', $g->id)->count();
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
