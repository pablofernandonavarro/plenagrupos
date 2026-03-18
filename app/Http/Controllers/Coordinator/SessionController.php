<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\TherapeuticSession;

class SessionController extends Controller
{
    public function show(TherapeuticSession $session)
    {
        // Ensure coordinator belongs to this session's group
        $coordinator = auth()->user();
        $groupIds = $coordinator->coordinatorGroups()->pluck('groups.id');

        if (!$groupIds->contains($session->group_id)) {
            abort(403);
        }

        $session->load(['group', 'attendances.user', 'weightRecords.user']);

        $avgWeight = $session->weightRecords->avg('weight');
        $maxWeight = $session->weightRecords->max('weight');
        $minWeight = $session->weightRecords->min('weight');

        return view('coordinator.sessions.show', compact('session', 'avgWeight', 'maxWeight', 'minWeight'));
    }
}
