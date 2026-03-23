<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupAttendance;
use App\Models\Group;
use App\Models\PlanRule;
use App\Models\User;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = GroupAttendance::with(['group', 'user', 'weightRecord'])
            ->orderBy('attended_at', 'desc');

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->filled('patient_id')) {
            $query->where('user_id', $request->patient_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('attended_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('attended_at', '<=', $request->date_to);
        }

        $attendances = $query->paginate(25)->withQueryString();

        $groups   = Group::orderBy('name')->get(['id', 'name']);
        $patients = User::where('role', 'patient')->orderBy('name')->get(['id', 'name', 'plan', 'plan_start_date']);

        $groupTypes  = ['descenso', 'mantenimiento', 'mantenimiento_pleno'];
        $rules       = PlanRule::all()->keyBy(fn($r) => $r->patient_plan . '.' . $r->group_type);

        // Summary: filter by patient if selected
        $summaryPatients = $request->filled('patient_id')
            ? $patients->where('id', (int) $request->patient_id)
            : $patients;
        $patientIds      = $summaryPatients->pluck('id');

        // All-time attendances for the detail modal
        $allAttendances = GroupAttendance::with('group')
            ->whereIn('user_id', $patientIds)
            ->orderBy('attended_at', 'desc')
            ->get()
            ->groupBy('user_id');

        // Build summary with per-patient 30-day cycle
        $summary = $summaryPatients->map(function ($patient) use ($groupTypes, $rules, $allAttendances) {
            [$cycleStart, $cycleEnd] = $patient->currentPlanCycle();

            // Count attendances within this patient's current cycle
            $cycleCounts = \App\Models\GroupAttendance::where('user_id', $patient->id)
                ->whereBetween('attended_at', [$cycleStart, $cycleEnd])
                ->join('groups', 'group_attendances.group_id', '=', 'groups.id')
                ->selectRaw('groups.group_type, COUNT(*) as total')
                ->groupBy('groups.group_type')
                ->get()
                ->keyBy('group_type');

            $row = [
                'patient'    => $patient,
                'cycleStart' => $cycleStart,
                'cycleEnd'   => $cycleEnd,
                'types'      => [],
                'attendances'=> $allAttendances->get($patient->id, collect()),
            ];

            foreach ($groupTypes as $gt) {
                $rule  = $rules->get("{$patient->plan}.{$gt}");
                $used  = (int) ($cycleCounts->get($gt)?->total ?? 0);
                $limit = $rule?->monthly_limit;
                $row['types'][$gt] = [
                    'used'      => $used,
                    'limit'     => $limit,
                    'remaining' => $limit !== null ? max(0, $limit - $used) : null,
                    'over'      => $limit !== null && $used > $limit,
                ];
            }
            return $row;
        })->values();

        return view('admin.attendances.index', compact('attendances', 'groups', 'patients', 'summary', 'groupTypes'));
    }

    public function destroy(GroupAttendance $attendance)
    {
        $attendance->delete();
        return back()->with('success', 'Asistencia eliminada.');
    }
}
