<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupAttendance;
use App\Models\User;
use App\Models\WeightRecord;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'groups'       => Group::count(),
            'coordinators' => User::where('role', 'coordinator')->count(),
            'patients'     => User::where('role', 'patient')->count(),
            'visits_today' => GroupAttendance::whereDate('attended_at', today())->count(),
        ];

        // Avg weight loss: first vs last record per patient (patients with ≥2 records)
        $patients = User::where('role', 'patient')
            ->with(['weightRecords' => fn($q) => $q->orderBy('recorded_at')])
            ->get();

        $losses = [];
        $inRangeCount = 0;
        $patientsWithRange = 0;
        foreach ($patients as $p) {
            $recs = $p->weightRecords;
            if ($recs->count() >= 2) {
                $losses[] = (float)$recs->first()->weight - (float)$recs->last()->weight;
            }
            if ($p->peso_piso && $p->peso_techo) {
                $patientsWithRange++;
                $last = $recs->last()?->weight;
                if ($last && (float)$last >= (float)$p->peso_piso && (float)$last <= (float)$p->peso_techo) {
                    $inRangeCount++;
                }
            }
        }
        $stats['avg_loss']         = count($losses) > 0 ? round(array_sum($losses) / count($losses), 1) : null;
        $stats['in_range']         = $inRangeCount;
        $stats['patients_range']   = $patientsWithRange;
        $stats['active_patients']  = User::where('role', 'patient')
            ->whereHas('attendances', fn($q) => $q->where('attended_at', '>=', now()->subDays(30)))
            ->count();

        // Weekly activity chart: weight records per week, last 8 weeks
        $weeklyData = collect(range(7, 0))->map(function ($weeksAgo) {
            $start = now()->startOfWeek()->subWeeks($weeksAgo);
            $end   = $start->copy()->endOfWeek();
            return [
                'label' => $start->format('d/m'),
                'count' => WeightRecord::whereBetween('recorded_at', [$start, $end])->count(),
            ];
        });

        $recentAttendances = GroupAttendance::with(['user', 'group', 'weightRecord'])
            ->latest('attended_at')
            ->limit(8)
            ->get();

        $groups = Group::with(['patients'])->latest()->get();

        return view('admin.dashboard', compact('stats', 'recentAttendances', 'groups', 'weeklyData'));
    }
}
