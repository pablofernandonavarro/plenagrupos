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

        // Weekly summary per patient
        $groupTypes = ['descenso', 'mantenimiento', 'mantenimiento_pleno'];
        $weekStart  = now()->startOfWeek();
        $rules      = PlanRule::all()->keyBy(fn($r) => $r->patient_plan . '.' . $r->group_type);

        // Count this-week attendances grouped by user_id + group_type
        $weekCounts = GroupAttendance::where('attended_at', '>=', $weekStart)
            ->join('groups', 'group_attendances.group_id', '=', 'groups.id')
            ->selectRaw('group_attendances.user_id, groups.group_type, COUNT(*) as total')
            ->groupBy('group_attendances.user_id', 'groups.group_type')
            ->get()
            ->groupBy('user_id')
            ->map(fn($rows) => $rows->keyBy('group_type'));

        $summary = $patients->filter(fn($p) => $p->plan)->map(function ($patient) use ($groupTypes, $rules, $weekCounts) {
            $row = ['patient' => $patient, 'types' => []];
            foreach ($groupTypes as $gt) {
                $rule    = $rules->get("{$patient->plan}.{$gt}");
                $used    = (int) ($weekCounts->get($patient->id)?->get($gt)?->total ?? 0);
                $limit   = $rule?->weekly_limit;
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
