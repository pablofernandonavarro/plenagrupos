<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $weightRecords = $user->weightRecords()
            ->with('group')
            ->latest('recorded_at')
            ->get();

        $latestWeight  = $weightRecords->first()?->weight;
        $initialWeight = $weightRecords->last()?->weight;
        $totalLoss     = ($initialWeight && $latestWeight) ? round($initialWeight - $latestWeight, 2) : null;

        $groups = $user->patientGroups()->get();

        // Chart data (chronological)
        $chartRecords = $weightRecords->sortBy('recorded_at')->values();

        // Linear regression trend (kg per session, negative = losing)
        $trend = 0;
        $n = $chartRecords->count();
        if ($n >= 2) {
            $x = range(0, $n - 1);
            $y = $chartRecords->pluck('weight')->map(fn($w) => (float) $w)->toArray();
            $meanX = array_sum($x) / $n;
            $meanY = array_sum($y) / $n;
            $num = 0; $den = 0;
            foreach ($x as $i => $xi) {
                $num += ($xi - $meanX) * ($y[$i] - $meanY);
                $den += ($xi - $meanX) ** 2;
            }
            $trend = $den > 0 ? round($num / $den, 3) : 0;
        }

        // Progress toward ideal weight (0–100%)
        $progressPct = null;
        if ($initialWeight && $user->ideal_weight && (float)$initialWeight !== (float)$user->ideal_weight) {
            $totalNeeded = (float)$initialWeight - (float)$user->ideal_weight;
            $achieved    = (float)$initialWeight - (float)$latestWeight;
            $progressPct = $totalNeeded != 0
                ? max(0, min(100, round($achieved / $totalNeeded * 100)))
                : null;
        }

        $piso  = $user->peso_piso;
        $techo = $user->peso_techo;
        $inRange = ($latestWeight && $piso && $techo)
            ? ((float)$latestWeight >= (float)$piso && (float)$latestWeight <= (float)$techo)
            : null;

        $chartData = [
            'labels'  => $chartRecords->map(fn($r) => $r->recorded_at->format('d/m'))->toArray(),
            'weights' => $chartRecords->map(fn($r) => (float) $r->weight)->toArray(),
            'piso'    => $piso  ? (float) $piso  : null,
            'techo'   => $techo ? (float) $techo : null,
        ];

        return view('patient.dashboard', compact(
            'weightRecords', 'latestWeight', 'totalLoss', 'groups',
            'trend', 'progressPct', 'inRange', 'chartData', 'piso', 'techo'
        ));
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'peso_piso'  => 'nullable|numeric|min:0|max:300',
            'peso_techo' => 'nullable|numeric|min:0|max:300',
        ]);

        auth()->user()->update([
            'peso_piso'  => $data['peso_piso'] ?? null,
            'peso_techo' => $data['peso_techo'] ?? null,
        ]);

        return back()->with('success', 'Rango de mantenimiento actualizado.');
    }
}
