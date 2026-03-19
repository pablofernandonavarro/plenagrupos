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

        // Total minutes: sum of each attended group's session duration
        $attendedGroupIds = $attendances->pluck('group_id')->unique();
        $totalMinutes = $groups->whereIn('id', $attendedGroupIds)->sum(function ($g) {
            if ($g->started_at && $g->ended_at) {
                return $g->started_at->diffInMinutes($g->ended_at);
            }
            if ($g->started_at && $g->active) {
                return $g->started_at->diffInMinutes(now());
            }
            return 0;
        });

        // Unified timeline: merge weight records into attendance entries
        $weightByAttendance = $weightRecords->keyBy(fn($w) =>
            $w->group_id . '_' . $w->recorded_at->format('Y-m-d')
        );

        $timeline = $attendances->map(function ($att) use ($weightByAttendance, $weightRecords) {
            $key = $att->group_id . '_' . $att->attended_at->format('Y-m-d');
            $weight = $weightByAttendance->get($key)?->weight;
            return [
                'date'       => $att->attended_at,
                'group_name' => $att->group->name,
                'weight'     => $weight,
            ];
        });

        // Add weight change to each timeline entry
        $timelineWithChange = $timeline->values()->map(function ($entry, $index) use ($timeline) {
            $next = $timeline->get($index + 1);
            $entry['change'] = ($entry['weight'] && $next && $next['weight'])
                ? round($entry['weight'] - $next['weight'], 2)
                : null;
            return $entry;
        });

        return view('coordinator.patients.show', compact(
            'patient', 'groups', 'weightRecords', 'attendances',
            'totalChange', 'firstWeight', 'lastWeight', 'totalMinutes', 'timelineWithChange'
        ));
    }
}
