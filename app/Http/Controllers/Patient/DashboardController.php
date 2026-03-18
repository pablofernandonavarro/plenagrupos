<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;

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
}
