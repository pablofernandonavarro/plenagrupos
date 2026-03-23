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
        } elseif ($request->input('status') === 'pending') {
            $query->where('active', false)->whereNull('started_at');
        } elseif ($request->input('status') === 'closed') {
            $query->where('active', false)->whereNotNull('started_at');
        }

        if ($coordinatorId = $request->input('coordinator_id')) {
            $query->whereHas('coordinators', fn($q) => $q->where('users.id', $coordinatorId));
        }

        if ($modality = $request->input('modality')) {
            $query->where('modality', $modality);
        }

        $coordinators = User::where('role', 'coordinator')->orderBy('name')->get();
        $groups = $query->paginate(10)->withQueryString();
        return view('admin.groups.index', compact('groups', 'coordinators'));
    }

    public function create()
    {
        $coordinators = User::where('role', 'coordinator')->get();
        return view('admin.groups.create', compact('coordinators'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'modality'            => 'required|in:presencial,virtual,hibrido',
            'group_type'          => 'required|in:descenso,mantenimiento',
            'description'         => 'nullable|string',
            'meeting_days'        => 'nullable|array',
            'meeting_days.*'      => 'in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo',
            'meeting_time'        => 'nullable|date_format:H:i',
            'recurrence_type'     => 'required|in:none,daily,weekly,monthly,yearly',
            'recurrence_interval' => 'nullable|integer|min:1|max:365',
            'recurrence_end_date' => 'nullable|date|after:today',
            'coordinator_ids'     => 'nullable|array',
            'coordinator_ids.*'   => 'exists:users,id',
        ]);

        $meetingDays = $data['recurrence_type'] === 'weekly' ? ($data['meeting_days'] ?? []) : null;
        $meetingDay  = !empty($meetingDays) ? $meetingDays[0] : null;

        $group = Group::create([
            'name'                => $data['name'],
            'modality'            => $data['modality'],
            'group_type'          => $data['group_type'],
            'description'         => $data['description'] ?? null,
            'meeting_day'         => $meetingDay,
            'meeting_days'        => $meetingDays,
            'meeting_time'        => $data['meeting_time'] ?? null,
            'recurrence_type'     => $data['recurrence_type'],
            'recurrence_interval' => $data['recurrence_interval'] ?? 1,
            'recurrence_end_date' => $data['recurrence_end_date'] ?? null,
            'auto_sessions'       => $data['recurrence_type'] !== 'none',
            'admin_id'            => auth()->id(),
            'active'              => false,
        ]);

        if (!empty($data['coordinator_ids'])) {
            $group->coordinators()->sync($data['coordinator_ids']);
        }

        return redirect()->route('admin.groups.show', $group)->with('success', 'Grupo creado exitosamente.');
    }

    public function edit(Group $group)
    {
        $group->load('coordinators');
        $coordinators = User::where('role', 'coordinator')->orderBy('name')->get();
        return view('admin.groups.edit', compact('group', 'coordinators'));
    }

    public function update(Request $request, Group $group)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'modality'            => 'required|in:presencial,virtual,hibrido',
            'group_type'          => 'required|in:descenso,mantenimiento',
            'description'         => 'nullable|string',
            'meeting_days'        => 'nullable|array',
            'meeting_days.*'      => 'in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo',
            'meeting_time'        => 'nullable|date_format:H:i,H:i:s',
            'recurrence_type'     => 'required|in:none,daily,weekly,monthly,yearly',
            'recurrence_interval' => 'nullable|integer|min:1|max:365',
            'recurrence_end_date' => 'nullable|date',
            'coordinator_ids'     => 'nullable|array',
            'coordinator_ids.*'   => 'exists:users,id',
        ]);

        $meetingDays = $data['recurrence_type'] === 'weekly' ? ($data['meeting_days'] ?? []) : null;
        $meetingDay  = !empty($meetingDays) ? $meetingDays[0] : null;

        $group->update([
            'name'                => $data['name'],
            'modality'            => $data['modality'],
            'group_type'          => $data['group_type'],
            'description'         => $data['description'] ?? null,
            'meeting_day'         => $meetingDay,
            'meeting_days'        => $meetingDays,
            'meeting_time'        => $data['meeting_time'] ? substr($data['meeting_time'], 0, 5) : null,
            'recurrence_type'     => $data['recurrence_type'],
            'recurrence_interval' => $data['recurrence_interval'] ?? 1,
            'recurrence_end_date' => $data['recurrence_end_date'] ?? null,
            'auto_sessions'       => $data['recurrence_type'] !== 'none',
        ]);

        $group->coordinators()->sync($data['coordinator_ids'] ?? []);

        return redirect()->route('admin.groups.show', $group)->with('success', 'Grupo actualizado.');
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

        $avg = $group->weightRecords()->whereDate('recorded_at', today())->avg('weight');

        return response()->json([
            'count'       => $attendances->count(),
            'avg_weight'  => $avg ? number_format($avg, 1) : null,
            'attendances' => $attendances,
        ]);
    }

    public function toggle(Group $group)
    {
        if (!$group->active) {
            return back()->with('error', 'Un grupo finalizado no puede volver a iniciarse.');
        }
        $group->update(['active' => false, 'ended_at' => now()]);
        return back()->with('success', 'Grupo finalizado.');
    }

    public function destroy(Group $group)
    {
        $group->delete();
        return redirect()->route('admin.groups.index')->with('success', 'Grupo eliminado.');
    }
}
