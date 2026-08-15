<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppointmentRequirement;
use App\Models\GroupAttendance;
use App\Models\InbodyRecord;
use App\Models\User;
use App\Models\WeightRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientAdherenceController extends Controller
{
    public function index(Request $request): View
    {
        $alertDaysAtt    = max(1, min(365, (int) $request->input('alert_days_att',    14)));
        $alertDaysWeight = max(1, min(365, (int) $request->input('alert_days_weight', 14)));
        $alertDaysInbody = max(1, min(365, (int) $request->input('alert_days_inbody', 30)));
        $onlyAlerts      = $request->boolean('solo_alertas');
        $search          = trim((string) $request->input('search', ''));

        $lastAtt = GroupAttendance::query()
            ->selectRaw('user_id, MAX(attended_at) as last_at')
            ->groupBy('user_id')
            ->pluck('last_at', 'user_id');

        $lastWeight = WeightRecord::query()
            ->selectRaw('user_id, MAX(recorded_at) as last_at')
            ->groupBy('user_id')
            ->pluck('last_at', 'user_id');

        $lastInbody = InbodyRecord::query()
            ->selectRaw('user_id, MAX(test_date) as last_at')
            ->groupBy('user_id')
            ->pluck('last_at', 'user_id');

        $allRequirements = AppointmentRequirement::all()->keyBy(fn ($r) => $r->patient_plan.'.'.$r->specialty);

        $tz  = 'America/Argentina/Buenos_Aires';
        $now = Carbon::now($tz)->startOfDay();

        $rows = User::query()
            ->where('role', 'patient')
            ->when($search !== '', fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->get()
            ->map(function (User $patient) use (
                $lastAtt, $lastWeight, $lastInbody, $now, $alertDaysAtt, $alertDaysWeight, $alertDaysInbody, $tz,
                $allRequirements
            ) {
                $plan = $patient->plan;
                $attAt = $lastAtt[$patient->id] ?? null;
                $wAt   = $lastWeight[$patient->id] ?? null;
                $inAt  = $lastInbody[$patient->id] ?? null;

                $attCarbon = $attAt ? Carbon::parse($attAt)->timezone($tz) : null;
                $wCarbon   = $wAt   ? Carbon::parse($wAt)->timezone($tz)   : null;
                $inCarbon  = $inAt  ? Carbon::parse($inAt)->timezone($tz)  : null;

                $daysAtt = $attCarbon ? max(0, (int) $attCarbon->copy()->startOfDay()->diffInDays($now)) : null;
                $daysW   = $wCarbon   ? max(0, (int) $wCarbon->copy()->startOfDay()->diffInDays($now))   : null;
                $daysIn  = $inCarbon  ? max(0, (int) $inCarbon->copy()->startOfDay()->diffInDays($now))  : null;

                $attStale    = $attCarbon === null || $daysAtt > $alertDaysAtt;
                $weightStale = $wCarbon   === null || $daysW   > $alertDaysWeight;
                $inbodyStale = $inCarbon  === null || $daysIn  > $alertDaysInbody;

                $combinedTurnos = $patient->usesCombinedTurnoRequirement();

                if ($combinedTurnos) {
                    $combinedRequired = (int) ($allRequirements->get("{$plan}.cualquiera")?->monthly_required_count ?? 1);
                    $combinedCount = $patient->combinedTurnosThisMonth();
                    $combinedStale = $combinedCount < $combinedRequired;
                    $combinedState = $patient->combinedMonthlyTurnoState();
                    $medicoCount = $nutriCount = $medicoRequired = $nutriRequired = null;
                    $medicoState = $nutriState = null;
                    $medicoStale = $nutriStale = false;
                } else {
                    $medicoRequired = (int) ($allRequirements->get("{$plan}.medico")?->monthly_required_count ?? 1);
                    $nutriRequired  = (int) ($allRequirements->get("{$plan}.nutricionista")?->monthly_required_count ?? 1);
                    $medicoCount = $patient->turnosThisMonth('medico');
                    $nutriCount  = $patient->turnosThisMonth('nutricionista');
                    $medicoStale = $medicoCount < $medicoRequired;
                    $nutriStale  = $nutriCount  < $nutriRequired;
                    $medicoState = $patient->monthlyTurnoState('medico');
                    $nutriState  = $patient->monthlyTurnoState('nutricionista');
                    $combinedRequired = $combinedCount = $combinedState = null;
                    $combinedStale = false;
                }

                return [
                    'patient'         => $patient,
                    'lastAtt'         => $attCarbon,
                    'lastWeight'      => $wCarbon,
                    'lastInbody'      => $inCarbon,
                    'daysAtt'         => $daysAtt,
                    'daysW'           => $daysW,
                    'daysIn'          => $daysIn,
                    'attStale'        => $attStale,
                    'weightStale'     => $weightStale,
                    'inbodyStale'     => $inbodyStale,
                    'combinedTurnos'  => $combinedTurnos,
                    'medicoCount'     => $medicoCount,
                    'nutriCount'      => $nutriCount,
                    'medicoRequired'  => $medicoRequired,
                    'nutriRequired'   => $nutriRequired,
                    'medicoStale'     => $medicoStale,
                    'nutriStale'      => $nutriStale,
                    'medicoState'     => $medicoState,
                    'nutriState'      => $nutriState,
                    'combinedCount'   => $combinedCount,
                    'combinedRequired'=> $combinedRequired,
                    'combinedStale'   => $combinedStale,
                    'combinedState'   => $combinedState,
                    'needsAttention'  => $attStale || $weightStale || $inbodyStale || $medicoStale || $nutriStale || $combinedStale,
                ];
            });

        if ($onlyAlerts) {
            $rows = $rows->filter(fn (array $r) => $r['needsAttention'])->values();
        }

        return view('admin.adherence.index', [
            'rows'            => $rows,
            'alertDaysAtt'    => $alertDaysAtt,
            'alertDaysWeight' => $alertDaysWeight,
            'alertDaysInbody' => $alertDaysInbody,
            'onlyAlerts'      => $onlyAlerts,
            'search'          => $search,
        ]);
    }
}
