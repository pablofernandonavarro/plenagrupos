<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupAttendance;
use App\Models\GroupMembershipLog;
use App\Models\PlanRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GroupJoinController extends Controller
{
    /** @return array<string, string> */
    private function utmFromRequest(Request $request): array
    {
        return array_filter([
            'utm_source' => $request->query('utm_source'),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'utm_content' => $request->query('utm_content'),
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function groupJoinUrl(string $token, Request $request): string
    {
        return route('group.join', array_merge(['token' => $token], $this->utmFromRequest($request)));
    }

    public function show(Request $request, string $token)
    {
        $group = Group::where('qr_token', $token)->firstOrFail();

        $utm = $this->utmFromRequest($request);
        if ($utm !== []) {
            session(['group_join_utm.'.$token => $utm]);
        }

        if (! auth()->check()) {
            return redirect()->route('login', ['redirect' => $this->groupJoinUrl($token, $request)]);
        }

        $user = auth()->user();

        if (! $user->isPatient()) {
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

    public function join(Request $request, string $token)
    {
        $group = Group::where('qr_token', $token)->firstOrFail();

        if (! auth()->check()) {
            return redirect()->route('login', ['redirect' => $this->groupJoinUrl($token, $request)]);
        }

        $user = auth()->user();

        if (! $user->isPatient()) {
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

        // Límites según plan_rules: clave = fase efectiva (fase_actual o, si no hay, plan contratado)
        $faseParaReglas = $user->faseEfectiva();
        if ($faseParaReglas) {
            $rule = PlanRule::where('patient_plan', $faseParaReglas)
                ->where('group_type', $group->group_type)
                ->first();

            if ($rule && $rule->monthly_limit !== null) {
                $isWeekend = now()->isWeekend();

                if (! ($isWeekend && $rule->weekend_unlimited)) {
                    [$cycleStart, $cycleEnd] = $user->currentPlanCycle();

                    $monthlyCount = GroupAttendance::where('user_id', $user->id)
                        ->whereHas('group', fn ($q) => $q->where('group_type', $group->group_type))
                        ->whereBetween('attended_at', [$cycleStart, $cycleEnd])
                        ->count();

                    if ($monthlyCount >= $rule->monthly_limit) {
                        $typeLabels = [
                            'descenso' => 'descenso de peso',
                            'mantenimiento' => 'mantenimiento',
                            'mantenimiento_pleno' => 'mantenimiento pleno',
                        ];
                        $label = $typeLabels[$group->group_type] ?? $group->group_type;

                        return back()->with('error',
                            "Llegaste al límite mensual de {$rule->monthly_limit} grupo(s) de {$label} para tu fase aplicable (fase clínica o plan contratado).");
                    }
                }
            }
        }

        // Auto-record session start for recurring groups on first scan of the day
        if (($group->attributes['recurrence_type'] ?? 'none') !== 'none') {
            $tz = 'America/Argentina/Buenos_Aires';
            $todayStart = Carbon::today($tz);
            if (! $group->getRawOriginal('started_at') ||
                Carbon::parse($group->getRawOriginal('started_at'))->lt($todayStart)) {
                $group->started_at = now();
                $group->ended_at = null;
                $group->saveQuietly();
            }
        }

        // Register attendance for this visit
        $attendance = GroupAttendance::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'attended_at' => now(),
        ]);

        // Membership: join new, rejoin, or already active (just record attendance)
        $utm = session()->pull('group_join_utm.'.$token, []);
        $pivotData = [
            'joined_at'               => now(),
            'left_at'                 => null,
            'join_source'             => 'qr',
            'utm_source'              => $utm['utm_source'] ?? null,
            'utm_medium'              => $utm['utm_medium'] ?? null,
            'utm_campaign'            => $utm['utm_campaign'] ?? null,
            'utm_content'             => $utm['utm_content'] ?? null,
            'first_device_user_agent' => Str::limit((string) $request->userAgent(), 2000),
        ];

        // Check for any existing pivot row (active or inactive)
        $existingPivot = \Illuminate\Support\Facades\DB::table('group_patient')
            ->where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $existingPivot) {
            // First time joining
            $group->patients()->attach($user->id, $pivotData);
            GroupMembershipLog::create([
                'group_id'   => $group->id,
                'user_id'    => $user->id,
                'joined_at'  => now(),
                'join_source' => 'qr',
            ]);
        } elseif ($existingPivot->left_at !== null) {
            // Rejoining after having left
            $group->patients()->updateExistingPivot($user->id, $pivotData);
            GroupMembershipLog::create([
                'group_id'   => $group->id,
                'user_id'    => $user->id,
                'joined_at'  => now(),
                'join_source' => 'qr',
            ]);
        }
        // else: already active member, no pivot/log change needed

        return redirect()->route('patient.weight.create', ['attendance' => $attendance->id])
            ->with('success', '¡Bienvenido! Registrá tu peso para continuar.');
    }
}
