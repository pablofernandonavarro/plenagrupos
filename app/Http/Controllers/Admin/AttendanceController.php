<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupAttendance;
use App\Models\Group;
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
        $patients = User::where('role', 'patient')->orderBy('name')->get(['id', 'name']);

        return view('admin.attendances.index', compact('attendances', 'groups', 'patients'));
    }

    public function destroy(GroupAttendance $attendance)
    {
        $attendance->delete();
        return back()->with('success', 'Asistencia eliminada.');
    }
}
