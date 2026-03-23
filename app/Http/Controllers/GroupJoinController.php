<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupAttendance;
use App\Models\PlanRule;

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

        if ($group->status !== 'active') {
            return view('group.join', ['group' => $group, 'groupStatus' => $group->status]);
        }

        $alreadyCheckedIn = GroupAttendance::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->whereDate('attended_at', today())
            ->exists();

        return view('group.join', ['group' => $group, 'groupStatus' => 'active', 'alreadyCheckedIn' => $alreadyCheckedIn]);
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

        if ($group->status !== 'active') {
            return back()->with('error', $group->status === 'pending'
                ? 'Este grupo aún no fue iniciado por el coordinador.'
                : 'Este grupo está finalizado.');
        }

        $alreadyCheckedIn = GroupAttendance::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->whereDate('attended_at', today())
            ->exists();

        if ($alreadyCheckedIn) {
            return redirect()->route('patient.dashboard')
                ->with('info', 'Ya registraste tu asistencia a este grupo hoy.');
        }

        // Enforce plan rules
        if ($user->plan) {
            $rule = PlanRule::where('patient_plan', $user->plan)
                ->where('group_type', $group->group_type)
                ->first();

            if ($rule && $rule->weekly_limit !== null) {
                $isWeekend = now()->isWeekend();

                if (!($isWeekend && $rule->weekend_unlimited)) {
                    // Count how many times the patient attended groups of this type this week
                    $weekStart = now()->startOfWeek();
                    $weeklyCount = GroupAttendance::where('user_id', $user->id)
                        ->whereHas('group', fn($q) => $q->where('group_type', $group->group_type))
                        ->where('attended_at', '>=', $weekStart)
                        ->count();

                    if ($weeklyCount >= $rule->weekly_limit) {
                        $typeLabels = [
                            'descenso'           => 'descenso de peso',
                            'mantenimiento'      => 'mantenimiento',
                            'mantenimiento_pleno'=> 'mantenimiento pleno',
                        ];
                        $label = $typeLabels[$group->group_type] ?? $group->group_type;
                        return back()->with('error',
                            "Llegaste al límite semanal de {$rule->weekly_limit} grupo(s) de {$label} para tu plan.");
                    }
                }
            }
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
