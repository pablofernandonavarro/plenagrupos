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

        return view('group.join', ['group' => $group, 'groupClosed' => false]);
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
