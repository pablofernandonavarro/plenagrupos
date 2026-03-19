<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'patient')
            ->with(['patientGroups']);

        if ($search = $request->input('search')) {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
            );
        }

        $patients = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('coordinator.patients.index', compact('patients'));
    }

    public function show(User $patient)
    {
        $groups = $patient->patientGroups()->get();

        $weightRecords = $patient->weightRecords()
            ->with('group')
            ->latest('recorded_at')
            ->get();

        $attendances = $patient->attendances()
            ->with('group')
            ->latest('attended_at')
            ->get();

        $firstWeight = $weightRecords->last()?->weight;
        $lastWeight  = $weightRecords->first()?->weight;
        $totalChange = ($firstWeight && $lastWeight) ? round($lastWeight - $firstWeight, 2) : null;

        return view('coordinator.patients.show', compact(
            'patient', 'groups', 'weightRecords', 'attendances', 'totalChange', 'firstWeight', 'lastWeight'
        ));
    }
}
