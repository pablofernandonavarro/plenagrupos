<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupAttendance;

class GroupJoinController extends Controller
{
    public function show(string $token)
    {
        $group = Group::where('qr_token', $token)->firstOrFail();

        if (!auth()->check()) {
            return redirect()->route('login', ['redirect' => route('group.join', $token)]);
        }

        $user = auth()->user();

        if (!$user->isPatient()) {
            return redirect()->route('admin.dashboard')->with('info', 'Solo los pacientes pueden registrarse por QR.');
        }

        if (!$group->active) {
            return view('group.join', ['group' => $group, 'groupClosed' => true]);
        }

        $alreadyCheckedIn = GroupAttendance::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->whereDate('attended_at', today())
            ->exists();

        return view('group.join', ['group' => $group, 'groupClosed' => false, 'alreadyCheckedIn' => $alreadyCheckedIn]);
    }

    public function join(string $token)
    {
        $group = Group::where('qr_token', $token)->firstOrFail();

        if (!auth()->check()) {
            return redirect()->route('login', ['redirect' => route('group.join', $token)]);
        }

        $user = auth()->user();

        if (!$user->isPatient()) {
            return redirect()->route('admin.dashboard');
        }

        if (!$group->active) {
            return back()->with('error', 'Este grupo no está activo.');
        }

        $alreadyCheckedIn = GroupAttendance::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->whereDate('attended_at', today())
            ->exists();

        if ($alreadyCheckedIn) {
            return redirect()->route('patient.dashboard')
                ->with('info', 'Ya registraste tu asistencia a este grupo hoy.');
        }

        // Register attendance for this visit
        $attendance = GroupAttendance::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'attended_at' => now(),
        ]);

        // Also add to group_patient if not already a member
        $group->patients()->syncWithoutDetaching([$user->id => ['joined_at' => now()]]);

        return redirect()->route('patient.weight.create', ['attendance' => $attendance->id])
            ->with('success', '¡Bienvenido! Registrá tu peso para continuar.');
    }
}
