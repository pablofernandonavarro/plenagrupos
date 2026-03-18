<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $query = Group::with(['coordinators', 'patients'])->latest();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($request->input('status') === 'active') {
            $query->where('active', true);
        } elseif ($request->input('status') === 'closed') {
            $query->where('active', false);
        }

        $groups = $query->get();
        return view('admin.groups.index', compact('groups'));
    }

    public function create()
    {
        $coordinators = User::where('role', 'coordinator')->get();
        return view('admin.groups.create', compact('coordinators'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_day' => 'nullable|string|max:100',
            'meeting_time' => 'nullable|date_format:H:i',
            'coordinator_ids' => 'nullable|array',
            'coordinator_ids.*' => 'exists:users,id',
        ]);

        $group = Group::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'meeting_day' => $data['meeting_day'] ?? null,
            'meeting_time' => $data['meeting_time'] ?? null,
            'admin_id' => auth()->id(),
        ]);

        if (!empty($data['coordinator_ids'])) {
            $group->coordinators()->sync($data['coordinator_ids']);
        }

        return redirect()->route('admin.groups.show', $group)->with('success', 'Grupo creado exitosamente.');
    }

    public function show(Group $group)
    {
        $group->load(['coordinators', 'patients']);
        $allCoordinators = User::where('role', 'coordinator')->get();
        $allPatients = User::where('role', 'patient')->get();

        $joinUrl = route('group.join', $group->qr_token);
        $qrCode = QrCode::size(220)->generate($joinUrl);

        // Attendance stats
        $attendances = $group->attendances()->with(['user', 'weightRecord'])->latest('attended_at')->get();
        $totalVisits = $attendances->count();
        $avgWeight = $group->weightRecords()->avg('weight');

        return view('admin.groups.show', compact(
            'group', 'allCoordinators', 'allPatients',
            'qrCode', 'joinUrl', 'attendances', 'totalVisits', 'avgWeight'
        ));
    }

    public function addCoordinator(Request $request, Group $group)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $group->coordinators()->syncWithoutDetaching([$request->user_id]);
        return back()->with('success', 'Coordinador agregado.');
    }

    public function removeCoordinator(Request $request, Group $group)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $group->coordinators()->detach($request->user_id);
        return back()->with('success', 'Coordinador removido.');
    }

    public function addPatient(Request $request, Group $group)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $group->patients()->syncWithoutDetaching([$request->user_id => ['joined_at' => now()]]);
        return back()->with('success', 'Paciente agregado.');
    }

    public function removePatient(Request $request, Group $group)
    {
        if (!$group->active) {
            return back()->with('error', 'No se pueden remover pacientes de un grupo finalizado.');
        }
        $request->validate(['user_id' => 'required|exists:users,id']);
        $group->patients()->detach($request->user_id);
        return back()->with('success', 'Paciente removido.');
    }

    public function liveAttendances(Group $group)
    {
        $attendances = $group->attendances()
            ->with(['user', 'weightRecord'])
            ->whereDate('attended_at', today())
            ->latest('attended_at')
            ->get()
            ->map(fn($a) => [
                'id'           => $a->user_id,
                'name'         => $a->user->name,
                'attended_at'  => $a->attended_at->format('H:i'),
                'weight'       => $a->weightRecord?->weight,
                'ideal_weight' => $a->user->ideal_weight,
            ]);

        return response()->json([
            'count'       => $attendances->count(),
            'attendances' => $attendances,
        ]);
    }

    public function toggle(Group $group)
    {
        if (!$group->active) {
            return back()->with('error', 'Un grupo finalizado no puede volver a iniciarse.');
        }
        $group->active = false;
        $group->save();
        return back()->with('success', 'Grupo finalizado.');
    }

    public function destroy(Group $group)
    {
        $group->delete();
        return redirect()->route('admin.groups.index')->with('success', 'Grupo eliminado.');
    }
}
