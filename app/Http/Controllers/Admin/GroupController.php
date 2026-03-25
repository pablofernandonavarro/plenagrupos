<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $tz = 'America/Argentina/Buenos_Aires';
        $now = Carbon::now($tz);
        $dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $todayName = $dayNames[$now->dayOfWeek];
        $timeNow = $now->format('H:i:s');

        $status = $request->input('status', 'today');

        if ($status === 'today') {
            // Grupos que tienen sesión HOY (sin importar la hora exacta)
            $query = Group::with(['coordinators', 'patients'])
                ->orderByRaw('CASE WHEN meeting_time IS NULL THEN 1 ELSE 0 END')
                ->orderBy('meeting_time');
            if ($search = $request->input('search')) {
                $query->where('name', 'like', '%'.$search.'%');
            }
            $query->where(function ($q) use ($todayName) {
                $q->where('active', true)
                    ->orWhere(function ($q2) use ($todayName) {
                        $q2->whereNotIn('recurrence_type', ['none'])
                            ->where(fn ($q3) => $q3->whereNull('recurrence_end_date')
                                ->orWhere('recurrence_end_date', '>=', today()))
                            ->where(function ($q3) use ($todayName) {
                                // Diario/mensual/anual: siempre aplica hoy
                                $q3->whereIn('recurrence_type', ['daily', 'monthly', 'yearly'])
                                // Semanal: verificar si hoy está en meeting_days o meeting_day
                                    ->orWhere(function ($q4) use ($todayName) {
                                        $q4->where('recurrence_type', 'weekly')
                                            ->where(function ($q5) use ($todayName) {
                                                $q5->whereRaw('meeting_days LIKE ?', ['%"'.$todayName.'"%'])
                                                    ->orWhere('meeting_day', $todayName);
                                            });
                                    });
                            });
                    });
            });
        } else {
            $query = Group::with(['coordinators', 'patients'])->latest();
            if ($search = $request->input('search')) {
                $query->where('name', 'like', '%'.$search.'%');
            }
            if ($status === 'active') {
                // Todos los programas vigentes (no vencidos)
                $query->where(function ($q) {
                    $q->where('active', true)
                        ->orWhere(function ($q2) {
                            $q2->whereNotIn('recurrence_type', ['none'])
                                ->where(fn ($q3) => $q3->whereNull('recurrence_end_date')
                                    ->orWhere('recurrence_end_date', '>=', today()));
                        });
                });
            } elseif ($status === 'closed') {
                $query->where(function ($q) {
                    $q->where('active', false)->whereNotNull('started_at')
                        ->where(fn ($q2) => $q2->where('recurrence_type', 'none')->orWhereNull('recurrence_type'));
                })->orWhere(function ($q) {
                    $q->whereNotIn('recurrence_type', ['none'])
                        ->whereNotNull('recurrence_end_date')
                        ->where('recurrence_end_date', '<', today());
                });
            }
            // $status === '' → Todos
        }

        if ($coordinatorId = $request->input('coordinator_id')) {
            $query->whereHas('coordinators', fn ($q) => $q->where('users.id', $coordinatorId));
        }

        if ($modality = $request->input('modality')) {
            $query->where('modality', $modality);
        }

        $perPage = in_array((int) $request->input('per_page'), [10, 25, 50]) ? (int) $request->input('per_page') : 10;
        $coordinators = User::where('role', 'coordinator')->orderBy('name')->get();
        $groups = $query->paginate($perPage)->withQueryString();

        return view('admin.groups.index', compact('groups', 'coordinators', 'status'));
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
            'modality' => 'required|in:presencial,virtual,hibrido',
            'group_type' => 'required|in:descenso,mantenimiento,mantenimiento_pleno',
            'description' => 'nullable|string',
            'meeting_days' => 'nullable|array',
            'meeting_days.*' => 'in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo',
            'meeting_time' => 'nullable|date_format:H:i',
            'session_duration_minutes' => 'nullable|integer|min:15|max:480',
            'recurrence_type' => 'required|in:none,daily,weekly,monthly,yearly',
            'recurrence_interval' => 'nullable|integer|min:1|max:365',
            'recurrence_end_date' => 'nullable|date|after:today',
            'coordinator_ids' => 'nullable|array',
            'coordinator_ids.*' => 'exists:users,id',
        ]);

        $meetingDays = $data['recurrence_type'] === 'weekly' ? ($data['meeting_days'] ?? []) : null;
        $meetingDay = ! empty($meetingDays) ? $meetingDays[0] : null;

        $group = Group::create([
            'name' => $data['name'],
            'modality' => $data['modality'],
            'group_type' => $data['group_type'],
            'description' => $data['description'] ?? null,
            'meeting_day' => $meetingDay,
            'meeting_days' => $meetingDays,
            'meeting_time' => $data['meeting_time'] ?? null,
            'session_duration_minutes' => $data['session_duration_minutes'] ?? 120,
            'recurrence_type' => $data['recurrence_type'],
            'recurrence_interval' => $data['recurrence_interval'] ?? 1,
            'recurrence_end_date' => $data['recurrence_end_date'] ?? null,
            'auto_sessions' => $data['recurrence_type'] !== 'none',
            'admin_id' => auth()->id(),
            'active' => false,
        ]);

        if (! empty($data['coordinator_ids'])) {
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
            'name' => 'required|string|max:255',
            'modality' => 'required|in:presencial,virtual,hibrido',
            'group_type' => 'required|in:descenso,mantenimiento,mantenimiento_pleno',
            'description' => 'nullable|string',
            'meeting_days' => 'nullable|array',
            'meeting_days.*' => 'in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo',
            'meeting_time' => 'nullable|date_format:H:i,H:i:s',
            'session_duration_minutes' => 'nullable|integer|min:15|max:480',
            'recurrence_type' => 'required|in:none,daily,weekly,monthly,yearly',
            'recurrence_interval' => 'nullable|integer|min:1|max:365',
            'recurrence_end_date' => 'nullable|date',
            'coordinator_ids' => 'nullable|array',
            'coordinator_ids.*' => 'exists:users,id',
        ]);

        $meetingDays = $data['recurrence_type'] === 'weekly' ? ($data['meeting_days'] ?? []) : null;
        $meetingDay = ! empty($meetingDays) ? $meetingDays[0] : null;

        $group->update([
            'name' => $data['name'],
            'modality' => $data['modality'],
            'group_type' => $data['group_type'],
            'description' => $data['description'] ?? null,
            'meeting_day' => $meetingDay,
            'meeting_days' => $meetingDays,
            'meeting_time' => $data['meeting_time'] ? substr($data['meeting_time'], 0, 5) : null,
            'session_duration_minutes' => $data['session_duration_minutes'] ?? 120,
            'recurrence_type' => $data['recurrence_type'],
            'recurrence_interval' => $data['recurrence_interval'] ?? 1,
            'recurrence_end_date' => $data['recurrence_end_date'] ?? null,
            'auto_sessions' => $data['recurrence_type'] !== 'none',
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
        $group->patients()->syncWithoutDetaching([$request->user_id => [
            'joined_at' => now(),
            'join_source' => 'manual',
        ]]);

        return back()->with('success', 'Paciente agregado.');
    }

    public function removePatient(Request $request, Group $group)
    {
        if (! $group->active) {
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
            ->map(fn ($a) => [
                'id' => $a->user_id,
                'name' => $a->user->name,
                'attended_at' => $a->attended_at->format('H:i'),
                'weight' => $a->weightRecord?->weight,
                'ideal_weight' => $a->user->ideal_weight,
            ]);

        $avg = $group->weightRecords()->whereDate('recorded_at', today())->avg('weight');

        return response()->json([
            'count' => $attendances->count(),
            'avg_weight' => $avg ? number_format($avg, 1) : null,
            'attendances' => $attendances,
        ]);
    }

    public function toggle(Group $group)
    {
        if (! $group->active) {
            return back()->with('error', 'Un grupo finalizado no puede volver a iniciarse.');
        }
        $group->update(['active' => false, 'ended_at' => now()]);

        return back()->with('success', 'Grupo finalizado.');
    }

    public function destroy(Group $group)
    {
        $hasData = $group->attendances()->exists() || $group->weightRecords()->exists();
        if ($hasData) {
            return back()->with('error', 'No se puede eliminar el grupo porque tiene estadísticas registradas. Finalizalo en su lugar.');
        }
        $group->delete();

        return redirect()->route('admin.groups.index')->with('success', 'Grupo eliminado.');
    }
}
