<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\BuildsGroupHistorial;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupAttendance;
use App\Models\GroupMembershipLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GroupController extends Controller
{
    use BuildsGroupHistorial;

    public function index(Request $request)
    {
        $tz = 'America/Argentina/Buenos_Aires';
        $now = Carbon::now($tz);

        $status = $request->input('status', 'today');

        if ($status === 'today') {
            // Candidatos: luego filtramos con Group::meetsOnDate() (misma lógica que el modelo / QR)
            $query = Group::with(['coordinators', 'patients'])
                ->orderByRaw('CASE WHEN meeting_time IS NULL THEN 1 ELSE 0 END')
                ->orderBy('meeting_time');
            if ($search = $request->input('search')) {
                $query->where('name', 'like', '%'.$search.'%');
            }
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

        if ($status === 'today') {
            $filtered = $query->get()->filter(fn (Group $group) => $group->meetsOnDate($now))->values();
            $page = LengthAwarePaginator::resolveCurrentPage();
            $groups = new LengthAwarePaginator(
                $filtered->forPage($page, $perPage)->values(),
                $filtered->count(),
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
            $groups->getCollection()->load(['groupSessions' => fn ($q) => $q->where('session_date', $now->toDateString())]);
        } else {
            $groups = $query->paginate($perPage)->withQueryString();
        }

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

    public function show(Request $request, Group $group)
    {
        $group->load(['coordinators', 'patients', 'patientsAll']);
        $allCoordinators = User::where('role', 'coordinator')->get();
        $allPatients = User::where('role', 'patient')->get();

        $joinUrl = route('group.join', $group->qr_token);
        $qrCode = QrCode::size(220)->generate($joinUrl);

        // Attendance stats
        $attendances = $group->attendances()->with(['user', 'weightRecord', 'groupSession'])->latest('attended_at')->get();
        $totalVisits = $attendances->count();
        $avgWeight = $group->weightRecords()->avg('weight');

        $todaySessionRecord = $group->groupSessions()
            ->where('session_date', Carbon::now('America/Argentina/Buenos_Aires')->toDateString())
            ->first();

        $tz = 'America/Argentina/Buenos_Aires';
        $endedAt = $group->getRawOriginal('ended_at');
        $sessionEndedToday = $endedAt && Carbon::parse($endedAt)->timezone($tz)->isToday();

        return view('admin.groups.show', array_merge(
            compact('group', 'allCoordinators', 'allPatients', 'qrCode', 'joinUrl', 'attendances', 'totalVisits', 'avgWeight', 'todaySessionRecord', 'sessionEndedToday'),
            $this->buildGroupHistorialData($group, $request),
            ['historialFormAction' => route('admin.groups.show', $group)]
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

        $now = now();

        // Check for existing pivot row (may have left before)
        $existing = DB::table('group_patient')
            ->where('group_id', $group->id)
            ->where('user_id', $request->user_id)
            ->first();

        if (! $existing) {
            $group->patients()->attach($request->user_id, [
                'joined_at' => $now,
                'join_source' => 'manual',
            ]);
        } elseif ($existing->left_at !== null) {
            DB::table('group_patient')
                ->where('group_id', $group->id)
                ->where('user_id', $request->user_id)
                ->update(['joined_at' => $now, 'left_at' => null, 'join_source' => 'manual']);
        } else {
            return back()->with('info', 'El paciente ya está activo en este grupo.');
        }

        GroupMembershipLog::create([
            'group_id' => $group->id,
            'user_id' => $request->user_id,
            'joined_at' => $now,
            'join_source' => 'manual',
        ]);

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
        $colors = ['#09cda6', '#3b82f6', '#8b5cf6', '#6366f1', '#f43f5e', '#f59e0b', '#06b6d4', '#10b981'];

        $tz = 'America/Argentina/Buenos_Aires';
        $todayDate = Carbon::now($tz)->toDateString();
        $todaySession = $group->groupSessions()
            ->where('session_date', $todayDate)
            ->first();

        $attendances = $group->attendances()
            ->with(['user', 'weightRecord', 'groupSession'])
            ->whereDate('attended_at', $todayDate)
            ->latest('attended_at')
            ->get()
            ->map(fn ($a) => [
                'attendance_id' => $a->id,
                'id' => $a->user_id,
                'name' => $a->user->name,
                'initials' => collect(explode(' ', $a->user->name))->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->join(''),
                'color' => $colors[$a->user_id % count($colors)],
                'avatar_url' => $a->user->avatar ? secure_asset('storage/'.$a->user->avatar) : null,
                'attended_at' => $a->attended_at->format('H:i'),
                'left_at' => $a->left_at?->format('H:i'),
                'weight' => $a->weightRecord?->weight,
                'ideal_weight' => $a->user->ideal_weight,
                'session_number' => $a->groupSession?->sequence_number,
            ]);

        $avg = $group->weightRecords()->whereDate('recorded_at', $todayDate)->avg('weight');

        $patients = $group->patientsAll()->get()->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'email' => $p->email,
            'initials' => collect(explode(' ', $p->name))->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->join(''),
            'color' => $colors[$p->id % count($colors)],
            'avatar_url' => $p->avatar ? secure_asset('storage/'.$p->avatar) : null,
            'joined_at' => $p->pivot->joined_at ? Carbon::parse($p->pivot->joined_at)->format('d/m/Y H:i') : null,
            'join_source' => $p->pivot->join_source,
            'utm_source' => $p->pivot->utm_source,
            'utm_campaign' => $p->pivot->utm_campaign,
            'left_at' => $p->pivot->left_at ? Carbon::parse($p->pivot->left_at)->format('d/m/Y H:i') : null,
        ]);

        return response()->json([
            'count' => $attendances->count(),
            'avg_weight' => $avg ? number_format($avg, 1) : null,
            'session_number' => $todaySession?->sequence_number,
            'attendances' => $attendances,
            'patients' => $patients,
        ]);
    }

    public function checkoutAttendance(Group $group, GroupAttendance $attendance)
    {
        abort_if($attendance->group_id !== $group->id, 404);

        $attendance->update(['left_at' => now()]);

        return response()->json(['left_at' => $attendance->left_at->format('H:i')]);
    }

    public function closeSession(Group $group)
    {
        $tz = 'America/Argentina/Buenos_Aires';
        $endedAt = $group->getRawOriginal('ended_at');
        $sessionEndedToday = $endedAt && Carbon::parse($endedAt)->timezone($tz)->isToday();

        if ($sessionEndedToday) {
            $group->update(['ended_at' => null]);
            return back()->with('success', 'Sesión de hoy reabierta.');
        }

        $now = now();
        $group->update(['ended_at' => $now]);
        GroupAttendance::where('group_id', $group->id)
            ->whereDate('attended_at', today())
            ->whereNull('left_at')
            ->update(['left_at' => $now]);

        return back()->with('success', 'Sesión de hoy finalizada. El programa continúa la próxima clase.');
    }

    public function toggle(Group $group)
    {
        $type = $group->recurrence_type ?? 'none';
        $isRecurring = $type !== 'none';
        $tz = 'America/Argentina/Buenos_Aires';

        if ($group->active) {
            $now = now();
            $updates = ['active' => false, 'ended_at' => $now];
            if ($isRecurring) {
                $updates['recurrence_end_date'] = Carbon::now($tz)->subDay()->startOfDay()->toDateString();
            }
            $group->update($updates);
            GroupAttendance::where('group_id', $group->id)
                ->whereDate('attended_at', today())
                ->whereNull('left_at')
                ->update(['left_at' => $now]);

            return back()->with('success', $isRecurring ? 'Programa finalizado.' : 'Grupo finalizado.');
        }

        if ($isRecurring && $group->isProgramVigente()) {
            $now = now();
            $group->update([
                'recurrence_end_date' => Carbon::now($tz)->subDay()->startOfDay()->toDateString(),
                'ended_at' => $group->ended_at ?? $now,
            ]);
            GroupAttendance::where('group_id', $group->id)
                ->whereDate('attended_at', today())
                ->whereNull('left_at')
                ->update(['left_at' => $now]);

            return back()->with('success', 'Programa finalizado.');
        }

        return back()->with('error', 'Un grupo finalizado no puede volver a iniciarse.');
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
