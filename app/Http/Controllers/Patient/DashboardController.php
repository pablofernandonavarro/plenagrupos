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

        $latestWeight = $weightRecords->first()?->weight;
        $initialWeight = $weightRecords->last()?->weight;
        $totalLoss = ($initialWeight && $latestWeight) ? round($initialWeight - $latestWeight, 2) : null;

        $groups = $user->patientGroups()->get();

        return view('patient.dashboard', compact('weightRecords', 'latestWeight', 'totalLoss', 'groups'));
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
