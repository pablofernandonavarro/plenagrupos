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
        $patients = User::where('role', 'patient')->orderBy('name')->get(['id', 'name', 'plan']);

        // Summary period: use filter dates if provided, otherwise current month
        $summaryFrom = $request->filled('date_from')
            ? \Carbon\Carbon::parse($request->date_from)->startOfDay()
            : now()->startOfMonth();
        $summaryTo   = $request->filled('date_to')
            ? \Carbon\Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfMonth();

        // Summary shows all patients (with or without plan)
        $summaryPatients = $patients;

        $groupTypes  = ['descenso', 'mantenimiento', 'mantenimiento_pleno'];
        $rules       = PlanRule::all()->keyBy(fn($r) => $r->patient_plan . '.' . $r->group_type);
        $patientIds  = $summaryPatients->pluck('id');

        // Counts for the summary period grouped by user_id + group_type
        $periodCounts = GroupAttendance::whereBetween('attended_at', [$summaryFrom, $summaryTo])
            ->whereIn('user_id', $patientIds)
            ->join('groups', 'group_attendances.group_id', '=', 'groups.id')
            ->selectRaw('group_attendances.user_id, groups.group_type, COUNT(*) as total')
            ->groupBy('group_attendances.user_id', 'groups.group_type')
            ->get()
            ->groupBy('user_id')
            ->map(fn($rows) => $rows->keyBy('group_type'));

        // All-time attendances for the detail modal
        $allAttendances = GroupAttendance::with('group')
            ->whereIn('user_id', $patientIds)
            ->orderBy('attended_at', 'desc')
            ->get()
            ->groupBy('user_id');

        $summary = $summaryPatients->map(function ($patient) use ($groupTypes, $rules, $periodCounts, $allAttendances) {
            $row = ['patient' => $patient, 'types' => [], 'attendances' => $allAttendances->get($patient->id, collect())];
            foreach ($groupTypes as $gt) {
                $rule    = $rules->get("{$patient->plan}.{$gt}");
                $used    = (int) ($periodCounts->get($patient->id)?->get($gt)?->total ?? 0);
                $limit   = $rule?->monthly_limit;
                $row['types'][$gt] = [
                    'used'      => $used,
                    'limit'     => $limit,
                    'remaining' => $limit !== null ? max(0, $limit - $used) : null,
                    'over'      => $limit !== null && $used > $limit,
                ];
            }
            return $row;
        })->values();

        return view('admin.attendances.index', compact('attendances', 'groups', 'patients', 'summary', 'groupTypes', 'summaryFrom', 'summaryTo'));
    }

    public function destroy(GroupAttendance $attendance)
    {
        $attendance->delete();
        return back()->with('success', 'Asistencia eliminada.');
    }
}
