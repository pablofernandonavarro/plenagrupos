<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\GroupAttendance;
use App\Models\WeightRecord;
use Illuminate\Http\Request;

class WeightController extends Controller
{
    public function create(Request $request)
    {
        $attendanceId = $request->query('attendance');
        $attendance = GroupAttendance::with('group')->findOrFail($attendanceId);

        if ($attendance->user_id !== auth()->id()) {
            abort(403);
        }

        $alreadyRecorded = WeightRecord::where('attendance_id', $attendance->id)->exists();
        if ($alreadyRecorded) {
            return redirect()->route('patient.dashboard')->with('info', 'Ya registraste tu peso para esta visita.');
        }

        return view('patient.weight.create', compact('attendance'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'attendance_id' => 'required|exists:group_attendances,id',
            'weight' => 'required|numeric|min:1|max:300',
            'notes' => 'nullable|string|max:500',
        ]);

        $attendance = GroupAttendance::findOrFail($data['attendance_id']);

        if ($attendance->user_id !== auth()->id()) {
            abort(403);
        }

        WeightRecord::create([
            'user_id' => auth()->id(),
            'group_id' => $attendance->group_id,
            'attendance_id' => $attendance->id,
            'weight' => $data['weight'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('patient.dashboard')->with('success', 'Peso registrado: ' . $data['weight'] . ' kg');
    }
}
