<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Concerns\BuildsGroupHistorial;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DashboardController extends Controller
{
    use BuildsGroupHistorial;

    public function index(Request $request)
    {
        $assignedGroupsCount = Group::whereHas('coordinators', fn ($q) => $q->where('users.id', auth()->id()))->count();

        $query = Group::whereHas('coordinators', fn ($q) => $q->where('users.id', auth()->id()))
            ->with(['patients'])
            ->withCount(['attendances', 'weightRecords']);

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $collection = $query->get();
        $totalAfterSearch = $collection->count();

        // live = sesión en curso ahora (ventana horaria). active = vigentes como admin.
        $allowedStatuses = ['', 'live', 'active', 'pending', 'closed'];
        $status = $request->input('status', '');
        if (! in_array($status, $allowedStatuses, true)) {
            $status = '';
        }
        if ($status === 'live') {
            $collection = $collection->filter(fn (Group $g) => $g->isLiveSessionNow())->values();
        } elseif ($status === 'active') {
            $collection = $collection->filter(fn (Group $g) => $g->isProgramVigente())->values();
        } elseif ($status === 'pending') {
            $collection = $collection->filter(fn (Group $g) => $g->isProgramPending())->values();
        } elseif ($status === 'closed') {
            $collection = $collection->filter(fn (Group $g) => $g->isProgramClosed())->values();
        }

        // Más próximo a hoy/ahora arriba: sesión en curso ahora primero, luego por próxima ocurrencia, nombre.
        // Nota: sortBy([...]) en Laravel espera comparadores (a,b); usar sort() con comparador explícito.
        $collection = $collection->sort(function (Group $a, Group $b) {
            $liveA = $a->isLiveSessionNow() ? 0 : 1;
            $liveB = $b->isLiveSessionNow() ? 0 : 1;
            if ($liveA !== $liveB) {
                return $liveA <=> $liveB;
            }
            $t = $a->nextOccurrenceForSort()->timestamp <=> $b->nextOccurrenceForSort()->timestamp;
            if ($t !== 0) {
                return $t;
            }

            return strcmp(mb_strtolower($a->name), mb_strtolower($b->name));
        })->values();

        $perPage = 10;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $groups = new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $groups->getCollection()->transform(function ($group) {
            $group->qrSvg = QrCode::size(180)->generate(route('group.join', $group->qr_token));

            return $group;
        });

        return view('coordinator.dashboard', [
            'groups' => $groups,
            'assignedGroupsCount' => $assignedGroupsCount,
            'totalAfterSearch' => $totalAfterSearch,
            'search' => $search,
        ]);
    }

    public function showGroup(Request $request, Group $group)
    {
        $this->ensureCoordinator($group);

        // Auto-close stale attendances before showing the view
        $this->autoCloseStaleAttendances($group);

        $group->load(['patientsAll', 'coordinators']);
        $attendances = $group->attendances()->with(['user', 'weightRecord', 'groupSession'])->latest('attended_at')->paginate(20);
        $avgWeight = $group->weightRecords()->avg('weight');
        $totalVisits = $group->attendances()->count();

        $tzAr = 'America/Argentina/Buenos_Aires';
        $todayDateAr = Carbon::now($tzAr)->toDateString();

        $todayAttendances = $group->attendances()
            ->with(['user', 'weightRecord', 'groupSession'])
            ->whereDate('attended_at', $todayDateAr)
            ->get();

        $stats = $this->weightRangeStats($todayAttendances);
        $todayVisits = $todayAttendances->count();

        $todaySessionRecord = $group->groupSessions()
            ->where('session_date', $todayDateAr)
            ->first();

        return view('coordinator.group', array_merge(
            compact('group', 'attendances', 'avgWeight', 'totalVisits', 'todayVisits', 'stats', 'todaySessionRecord'),
            $this->buildGroupHistorialData($group, $request),
            ['historialFormAction' => route('coordinator.groups.show', $group)]
        ));
    }

    public function updateMaintenanceWeight(Group $group, Request $request)
    {
        $this->ensureCoordinator($group);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'maintenance_weight' => 'nullable|numeric|min:1|max:300',
        ]);

        $group->patients()->updateExistingPivot($data['user_id'], [
            'maintenance_weight' => $data['maintenance_weight'] ?: null,
        ]);

        return back()->with('success', 'Peso de mantenimiento actualizado.');
    }

    public function liveAttendances(Group $group)
    {
        $this->ensureCoordinator($group);

        // Auto-close stale attendances before showing live view
        $this->autoCloseStaleAttendances($group);

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

        $patients = Cache::remember("group_{$group->id}_patients_all", 30, fn () => $group->patientsAll()->get())
            ->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'initials' => collect(explode(' ', $p->name))->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->join(''),
            'color' => $colors[$p->id % count($colors)],
            'avatar_url' => $p->avatar ? secure_asset('storage/'.$p->avatar) : null,
            'joined_at' => $p->pivot->joined_at ? Carbon::parse($p->pivot->joined_at)->format('d/m/Y H:i') : null,
            'left_at' => $p->pivot->left_at ? Carbon::parse($p->pivot->left_at)->format('d/m/Y H:i') : null,
            'is_active' => $p->pivot->left_at === null,
            'join_source' => $p->pivot->join_source,
        ]);

        return response()->json([
            'count' => $attendances->count(),
            'session_number' => $todaySession?->sequence_number,
            'attendances' => $attendances,
            'patients' => $patients,
        ]);
    }

    public function checkoutAttendance(Group $group, GroupAttendance $attendance)
    {
        $this->ensureCoordinator($group);

        abort_if($attendance->group_id !== $group->id, 404);

        $attendance->update(['left_at' => now()]);

        return response()->json(['left_at' => $attendance->left_at->format('H:i')]);
    }

    public function profile()
    {
        return view('coordinator.profile');
    }

    public function updateProfile(Request $request)
    {
        $request->validate(['avatar' => 'nullable|image|max:2048']);

        $user = auth()->user();

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
            $user->save();
        }

        return back()->with('success', 'Foto de perfil actualizada.');
    }

    public function toggleGroup(Group $group)
    {
        $this->ensureCoordinator($group);

        $type = $group->recurrence_type ?? 'none';
        $isRecurring = $type !== 'none';
        $tz = 'America/Argentina/Buenos_Aires';

        // Recurring programs: coordinators only close/reopen TODAY's session.
        // They cannot permanently end the program — that's an admin action.
        if ($isRecurring) {
            $endedAt = $group->getRawOriginal('ended_at');
            $sessionEndedToday = $endedAt && Carbon::parse($endedAt)->timezone($tz)->isToday() && !$group->isLiveSessionNow();

            if ($sessionEndedToday) {
                // Reopen the session (coordinator pressed the button again today)
                $group->update(['ended_at' => null, 'started_at' => now()]);
                return back()->with('success', 'Sesión de hoy reabierta.');
            }

            if ($group->status !== 'active') {
                // Start session manually (before the scheduled window)
                $group->update(['started_at' => now(), 'ended_at' => null]);
                return back()->with('success', 'Sesión iniciada.');
            }

            // Close today's session and mark exit for all patients still in
            $now = now();
            $group->update(['ended_at' => $now]);
            GroupAttendance::where('group_id', $group->id)
                ->whereDate('attended_at', today())
                ->whereNull('left_at')
                ->update(['left_at' => $now]);
            return back()->with('success', 'Sesión de hoy finalizada. El programa continúa la próxima clase.');
        }

        // Non-recurring: same as before
        if ($group->active) {
            $now = now();
            $group->update(['active' => false, 'ended_at' => $now]);
            GroupAttendance::where('group_id', $group->id)
                ->whereDate('attended_at', today())
                ->whereNull('left_at')
                ->update(['left_at' => $now]);
            return back()->with('success', 'Grupo finalizado.');
        }

        if ($group->started_at) {
            return back()->with('error', 'Un grupo finalizado no puede volver a iniciarse.');
        }

        $group->update(['active' => true, 'started_at' => now()]);
        return back()->with('success', 'Grupo iniciado.');
    }

    private function ensureCoordinator(Group $group): void
    {
        abort_unless(
            $group->coordinators()->where('users.id', auth()->id())->exists(),
            403
        );
    }

    private function autoCloseStaleAttendances(Group $group)
    {
        $tz = 'America/Argentina/Buenos_Aires';
        $now = Carbon::now($tz);
        $todayDate = $now->toDateString();

        // If session is not live, close all open attendances for today
        if (!$group->isLiveSessionNow()) {
            // Get session end time
            $sessionEnd = null;

            // Priority 1: If manually closed today, use that time
            $endedAt = $group->getRawOriginal('ended_at');
            if ($endedAt && Carbon::parse($endedAt)->timezone($tz)->isToday()) {
                $sessionEnd = Carbon::parse($endedAt)->timezone($tz);
            }
            // Priority 2: Use scheduled end time (meeting_time + duration)
            elseif ($group->meeting_time && $group->session_duration_minutes) {
                [$h, $m] = array_pad(explode(':', $group->meeting_time), 2, '0');
                $sessionEnd = $now->copy()->setTime((int) $h, (int) $m, 0)
                    ->addMinutes((int) $group->session_duration_minutes);

                // Don't use future time, cap at now
                if ($sessionEnd->isFuture()) {
                    $sessionEnd = $now;
                }
            }
            // Priority 3: Use current time as fallback
            else {
                $sessionEnd = $now;
            }

            // Close open attendances from today
            GroupAttendance::where('group_id', $group->id)
                ->whereDate('attended_at', $todayDate)
                ->whereNull('left_at')
                ->update(['left_at' => $sessionEnd]);
        }
    }
}
