<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    private function coordinatorGroupIds(): array
    {
        return auth()->user()->coordinatorGroups()->pluck('groups.id')->toArray();
    }

    public function index(Request $request)
    {
        $groupIds = $this->coordinatorGroupIds();

        $query = User::where('role', 'patient')
            ->whereHas('patientGroups', fn($q) => $q->whereIn('groups.id', $groupIds))
            ->with(['patientGroups' => fn($q) => $q->whereIn('groups.id', $groupIds)]);

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
        $groupIds = $this->coordinatorGroupIds();

        // Verify patient belongs to at least one of coordinator's groups
        if (!$patient->patientGroups()->whereIn('groups.id', $groupIds)->exists()) {
            abort(403);
        }

        $groups = $patient->patientGroups()
            ->whereIn('groups.id', $groupIds)
            ->get();

        $weightRecords = $patient->weightRecords()
            ->with('group')
            ->latest('recorded_at')
            ->get();

        $attendances = $patient->attendances()
            ->with('group')
            ->whereIn('group_id', $groupIds)
            ->latest('attended_at')
            ->get();

        // Weight stats
        $firstWeight = $weightRecords->last()?->weight;
        $lastWeight  = $weightRecords->first()?->weight;
        $totalChange = ($firstWeight && $lastWeight) ? round($lastWeight - $firstWeight, 2) : null;

        return view('coordinator.patients.show', compact(
            'patient', 'groups', 'weightRecords', 'attendances', 'totalChange', 'firstWeight', 'lastWeight'
        ));
    }
}
