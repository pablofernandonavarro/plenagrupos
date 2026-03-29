<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupAttendance;
use App\Models\InbodyRecord;
use App\Models\User;
use App\Models\WeightRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    private const WEEKS_WINDOW = 8;

    public function index()
    {
        return view('admin.analytics.index');
    }

    /**
     * Comparativa por grupo: asistencias/semana, % en rango, pérdida media (solo pesajes del grupo).
     */
    public function groups(Request $request)
    {
        // Permitir filtro personalizado de fechas o usar ventana por defecto
        $weeksWindow = $request->integer('weeks', self::WEEKS_WINDOW);
        $weeksWindow = max(1, min(52, $weeksWindow)); // Entre 1 y 52 semanas

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom && $dateTo) {
            $since = Carbon::parse($dateFrom)->startOfDay();
            $until = Carbon::parse($dateTo)->endOfDay();
            $weeksCount = max(1, $since->diffInWeeks($until));
        } else {
            $since = now()->subWeeks($weeksWindow)->startOfWeek();
            $until = now();
            $weeksCount = $weeksWindow;
        }

        $attendanceTotals = GroupAttendance::query()
            ->whereBetween('attended_at', [$since, $until])
            ->selectRaw('group_id, COUNT(*) as total')
            ->groupBy('group_id')
            ->pluck('total', 'group_id');

        $groups = Group::query()
            ->with(['patients' => fn ($q) => $q->orderBy('users.name')])
            ->orderBy('name')
            ->get();

        $rows = $groups->map(function (Group $group) use ($attendanceTotals, $weeksCount) {
            $total = (int) ($attendanceTotals[$group->id] ?? 0);
            $avgWeekly = round($total / $weeksCount, 2);

            $patientCount = $group->patients->count();

            $inRange = 0;
            $withRange = 0;
            $losses = [];

            foreach ($group->patients as $patient) {
                $records = WeightRecord::query()
                    ->where('user_id', $patient->id)
                    ->where('group_id', $group->id)
                    ->orderBy('recorded_at')
                    ->get();

                if ($records->count() >= 2) {
                    $losses[] = (float) $records->first()->weight - (float) $records->last()->weight;
                }

                if ($patient->peso_piso && $patient->peso_techo) {
                    $withRange++;
                    $last = $records->sortByDesc('recorded_at')->first()?->weight;
                    if ($last !== null
                        && (float) $last >= (float) $patient->peso_piso
                        && (float) $last <= (float) $patient->peso_techo) {
                        $inRange++;
                    }
                }
            }

            $rangePct = $withRange > 0 ? round($inRange / $withRange * 100) : null;
            $avgLoss = count($losses) > 0 ? round(array_sum($losses) / count($losses), 2) : null;

            return [
                'group' => $group,
                'patientCount' => $patientCount,
                'avgWeekly' => $avgWeekly,
                'rangePct' => $rangePct,
                'inRange' => $inRange,
                'withRange' => $withRange,
                'avgLoss' => $avgLoss,
                'lossN' => count($losses),
            ];
        });

        $sort = $request->input('sort', 'nombre');
        if ($sort === 'asistencias') {
            $rows = $rows->sortByDesc('avgWeekly')->values();
        } elseif ($sort === 'rango') {
            $rows = $rows->sortByDesc(fn ($r) => $r['rangePct'] ?? -1)->values();
        } elseif ($sort === 'perdida') {
            $rows = $rows->sortByDesc(fn ($r) => $r['avgLoss'] ?? -999)->values();
        } else {
            $rows = $rows->sortBy(fn ($r) => mb_strtolower($r['group']->name))->values();
        }

        return view('admin.analytics.groups', [
            'rows' => $rows,
            'weeksWindow' => $weeksCount,
            'sort' => $sort,
            'dateFrom' => $dateFrom ?? $since->format('Y-m-d'),
            'dateTo' => $dateTo ?? $until->format('Y-m-d'),
            'weeksInput' => $weeksWindow,
        ]);
    }

    /**
     * Evolución agregada InBody + muestra por paciente (primer vs último estudio).
     */
    public function inbody()
    {
        $from = now()->subMonths(14)->startOfMonth();

        $records = InbodyRecord::query()
            ->where('test_date', '>=', $from)
            ->orderBy('test_date')
            ->get(['test_date', 'visceral_fat_level', 'body_fat_percentage', 'skeletal_muscle_mass']);

        $monthly = $records->groupBy(fn (InbodyRecord $r) => $r->test_date->format('Y-m'))
            ->map(function ($chunk) {
                $n = $chunk->count();

                return [
                    'n' => $n,
                    'visceral' => round($chunk->avg(fn (InbodyRecord $r) => $r->visceral_fat_level ?? null), 2),
                    'fat_pct' => round($chunk->avg(fn (InbodyRecord $r) => $r->body_fat_percentage ?? null), 2),
                    'muscle' => round($chunk->avg(fn (InbodyRecord $r) => $r->skeletal_muscle_mass ?? null), 2),
                ];
            })
            ->sortKeys()
            ->slice(-12);

        $patientRows = User::query()
            ->where('role', 'patient')
            ->has('inbodyRecords', '>=', 2)
            ->with(['inbodyRecords' => fn ($q) => $q->orderBy('test_date')])
            ->get()
            ->map(function (User $user) {
                $sorted = $user->inbodyRecords;
                if ($sorted->count() < 2) {
                    return null;
                }
                $first = $sorted->first();
                $last = $sorted->last();

                return [
                    'user' => $user,
                    'first' => $first,
                    'last' => $last,
                    'dVisceral' => $this->delta($last->visceral_fat_level, $first->visceral_fat_level),
                    'dFat' => $this->delta($last->body_fat_percentage, $first->body_fat_percentage),
                    'dMuscle' => $this->delta($last->skeletal_muscle_mass, $first->skeletal_muscle_mass),
                ];
            })
            ->filter()
            ->sortByDesc(fn ($r) => $r['last']->test_date)
            ->take(25)
            ->values();

        $chartData = $monthly->map(fn (array $m, string $k) => array_merge(['key' => $k], $m))->values();

        return view('admin.analytics.inbody', [
            'monthly' => $monthly,
            'chartData' => $chartData,
            'patientRows' => $patientRows,
        ]);
    }

    private function delta(?float $last, ?float $first): ?float
    {
        if ($last === null || $first === null) {
            return null;
        }

        return round((float) $last - (float) $first, 2);
    }

    /**
     * Retención: visitas en ventanas 30/60 días y curva semanas 1–8 desde alta en grupo.
     */
    public function cohorts(Request $request)
    {
        $groupId = $request->integer('group_id') ?: null;

        $q = DB::table('group_patient')
            ->join('users', 'users.id', '=', 'group_patient.user_id')
            ->where(function ($q) {
                $q->whereNull('users.patient_status')
                    ->orWhere('users.patient_status', '<>', 'exited');
            });
        if ($groupId) {
            $q->where('group_patient.group_id', $groupId);
        }
        $enrollments = $q->get([
            'group_patient.user_id as user_id',
            'group_patient.group_id as group_id',
            'group_patient.joined_at as joined_at',
        ]);

        $now = Carbon::now();

        $eligible30 = $enrollments->filter(fn ($e) => Carbon::parse($e->joined_at)->addDays(30)->lte($now));
        $eligible60 = $enrollments->filter(fn ($e) => Carbon::parse($e->joined_at)->addDays(60)->lte($now));

        $countVisits = function ($collection, int $days) {
            $minVisits = 0;
            $atLeast1 = 0;
            $atLeast2 = 0;
            $atLeast4 = 0;

            foreach ($collection as $e) {
                $start = Carbon::parse($e->joined_at);
                $end = $start->copy()->addDays($days);
                $n = GroupAttendance::query()
                    ->where('group_id', $e->group_id)
                    ->where('user_id', $e->user_id)
                    ->whereBetween('attended_at', [$start, $end])
                    ->count();
                $minVisits += $n;
                if ($n >= 1) {
                    $atLeast1++;
                }
                if ($n >= 2) {
                    $atLeast2++;
                }
                if ($n >= 4) {
                    $atLeast4++;
                }
            }

            $c = $collection->count();

            return [
                'n' => $c,
                'avg' => $c > 0 ? round($minVisits / $c, 2) : null,
                'pct1' => $c > 0 ? round($atLeast1 / $c * 100) : null,
                'pct2' => $c > 0 ? round($atLeast2 / $c * 100) : null,
                'pct4' => $c > 0 ? round($atLeast4 / $c * 100) : null,
            ];
        };

        $stats30 = $countVisits($eligible30, 30);
        $stats60 = $countVisits($eligible60, 60);

        // Semanas 1–8: % con ≥1 visita en esa semana relativa al alta (solo filas donde ya pasó esa semana)
        $weekPct = [];
        for ($w = 0; $w < 8; $w++) {
            $eligible = 0;
            $withVisit = 0;
            foreach ($enrollments as $e) {
                $join = Carbon::parse($e->joined_at)->startOfDay();
                if ($join->copy()->addWeeks($w + 1)->gt($now)) {
                    continue;
                }
                $eligible++;
                $ws = $join->copy()->addWeeks($w);
                $we = $join->copy()->addWeeks($w + 1)->subSecond();
                $has = GroupAttendance::query()
                    ->where('group_id', $e->group_id)
                    ->where('user_id', $e->user_id)
                    ->whereBetween('attended_at', [$ws, $we])
                    ->exists();
                if ($has) {
                    $withVisit++;
                }
            }
            $weekPct[] = [
                'label' => 'S'.($w + 1),
                'pct' => $eligible > 0 ? round($withVisit / $eligible * 100) : null,
                'eligible' => $eligible,
            ];
        }

        $groups = Group::orderBy('name')->get(['id', 'name']);

        return view('admin.analytics.cohorts', [
            'stats30' => $stats30,
            'stats60' => $stats60,
            'weekPct' => $weekPct,
            'groups' => $groups,
            'groupId' => $groupId,
            'enrollmentN' => $enrollments->count(),
        ]);
    }
}
