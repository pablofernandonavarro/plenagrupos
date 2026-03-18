<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $query = auth()->user()->coordinatorGroups()
            ->with(['patients'])
            ->withCount(['attendances', 'weightRecords']);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($request->input('status') === 'active') {
            $query->where('active', true);
        } elseif ($request->input('status') === 'closed') {
            $query->where('active', false);
        }

        $groups = $query->get()->map(function ($group) {
            $group->qrSvg = QrCode::size(180)->generate(route('group.join', $group->qr_token));
            return $group;
        });

        return view('coordinator.dashboard', ['groups' => $groups]);
    }

    public function showGroup(\App\Models\Group $group)
    {
        $coordinator = auth()->user();
        if (!$coordinator->coordinatorGroups()->where('groups.id', $group->id)->exists()) {
            abort(403);
        }

        $group->load(['patients', 'coordinators']);
        $attendances = $group->attendances()->with(['user', 'weightRecord'])->latest('attended_at')->paginate(20);
        $avgWeight = $group->weightRecords()->avg('weight');
        $totalVisits = $group->attendances()->count();

        return view('coordinator.group', compact('group', 'attendances', 'avgWeight', 'totalVisits'));
    }

    public function updateMaintenanceWeight(\App\Models\Group $group, \Illuminate\Http\Request $request)
    {
        $coordinator = auth()->user();
        if (!$coordinator->coordinatorGroups()->where('groups.id', $group->id)->exists()) {
            abort(403);
        }

        $data = $request->validate([
            'user_id'            => 'required|exists:users,id',
            'maintenance_weight' => 'nullable|numeric|min:1|max:300',
        ]);

        $group->patients()->updateExistingPivot($data['user_id'], [
            'maintenance_weight' => $data['maintenance_weight'] ?: null,
        ]);

        return back()->with('success', 'Peso de mantenimiento actualizado.');
    }

    public function liveAttendances(\App\Models\Group $group)
    {
        $coordinator = auth()->user();
        if (!$coordinator->coordinatorGroups()->where('groups.id', $group->id)->exists()) {
            abort(403);
        }

        $attendances = $group->attendances()
            ->with(['user', 'weightRecord'])
            ->latest('attended_at')
            ->get()
            ->map(fn($a) => [
                'id'          => $a->user_id,
                'name'        => $a->user->name,
                'attended_at' => $a->attended_at->format('H:i'),
                'weight'      => $a->weightRecord?->weight,
            ]);

        return response()->json([
            'count'       => $attendances->count(),
            'attendances' => $attendances,
        ]);
    }

    public function toggleGroup(\App\Models\Group $group)
    {
        $coordinator = auth()->user();
        if (!$coordinator->coordinatorGroups()->where('groups.id', $group->id)->exists()) {
            abort(403);
        }

        if (!$group->active) {
            return back()->with('error', 'Un grupo finalizado no puede volver a iniciarse.');
        }

        $group->update(['active' => false]);
        return back()->with('success', 'Grupo finalizado.');
    }
}
