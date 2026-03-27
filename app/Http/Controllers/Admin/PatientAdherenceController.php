<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $alertDays = max(1, min(365, (int) $request->input('alert_days', 14)));
        $onlyAlerts = $request->boolean('solo_alertas');

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

        $tz  = 'America/Argentina/Buenos_Aires';
        $now = Carbon::now($tz)->startOfDay();

        $rows = User::query()
            ->where('role', 'patient')
            ->orderBy('name')
            ->get()
            ->map(function (User $patient) use ($lastAtt, $lastWeight, $lastInbody, $now, $alertDays, $tz) {
                $attAt = $lastAtt[$patient->id] ?? null;
                $wAt   = $lastWeight[$patient->id] ?? null;
                $inAt  = $lastInbody[$patient->id] ?? null;

                $attCarbon = $attAt ? Carbon::parse($attAt)->timezone($tz) : null;
                $wCarbon   = $wAt   ? Carbon::parse($wAt)->timezone($tz)   : null;
                $inCarbon  = $inAt  ? Carbon::parse($inAt)->timezone($tz)  : null;

                $daysAtt = $attCarbon ? max(0, (int) $attCarbon->startOfDay()->diffInDays($now)) : null;
                $daysW   = $wCarbon   ? max(0, (int) $wCarbon->startOfDay()->diffInDays($now))   : null;
                $daysIn  = $inCarbon  ? max(0, (int) $inCarbon->startOfDay()->diffInDays($now))  : null;

                $attStale = $attCarbon === null || $daysAtt > $alertDays;
                $weightStale = $wCarbon === null || $daysW > $alertDays;

                return [
                    'patient' => $patient,
                    'lastAtt' => $attCarbon,
                    'lastWeight' => $wCarbon,
                    'lastInbody' => $inCarbon,
                    'daysAtt' => $daysAtt,
                    'daysW' => $daysW,
                    'daysIn' => $daysIn,
                    'needsAttention' => $attStale || $weightStale,
                ];
            });

        if ($onlyAlerts) {
            $rows = $rows->filter(fn (array $r) => $r['needsAttention'])->values();
        }

        return view('admin.adherence.index', [
            'rows' => $rows,
            'alertDays' => $alertDays,
            'onlyAlerts' => $onlyAlerts,
        ]);
    }
}
