<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $assignedGroupsCount = Group::whereHas('coordinators', fn ($q) => $q->where('users.id', auth()->id()))->count();

        $query = Group::whereHas('coordinators', fn ($q) => $q->where('users.id', auth()->id()))
            ->with(['patients'])
            ->withCount(['attendances', 'weightRecords'])
            ->orderBy('created_at', 'desc');

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $collection = $query->get();
        $totalAfterSearch = $collection->count();

        $allowedStatuses = ['', 'active', 'pending', 'closed'];
        $status = $request->input('status', '');
        if (! in_array($status, $allowedStatuses, true)) {
            $status = '';
        }
        if ($status === 'active') {
            $collection = $collection->filter(fn (Group $g) => $g->isProgramVigente())->values();
        } elseif ($status === 'pending') {
            $collection = $collection->filter(fn (Group $g) => $g->isProgramPending())->values();
        } elseif ($status === 'closed') {
            $collection = $collection->filter(fn (Group $g) => $g->isProgramClosed())->values();
        }

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

    public function showGroup(Group $group)
    {
        $group->load(['patients', 'coordinators']);
        $attendances = $group->attendances()->with(['user', 'weightRecord'])->latest('attended_at')->paginate(20);
        $avgWeight = $group->weightRecords()->avg('weight');
        $totalVisits = $group->attendances()->count();

        $todayAttendances = $group->attendances()
            ->with(['user', 'weightRecord'])
            ->whereDate('attended_at', today())
            ->get();

        $inRange = 0;
        $above = 0;
        $below = 0;
        $noWeight = 0;
        foreach ($todayAttendances as $a) {
            $rw = $a->weightRecord?->weight;
            $piso = $a->user->peso_piso;
            $techo = $a->user->peso_techo;
            if (! $rw) {
                $noWeight++;
            } elseif ($techo && $rw > $techo) {
                $above++;
            } elseif ($piso && $rw < $piso) {
                $below++;
            } elseif ($piso || $techo) {
                $inRange++;
            } else {
                $noWeight++;
            }
        }

        $todayVisits = $todayAttendances->count();
        $stats = compact('inRange', 'above', 'below', 'noWeight');

        return view('coordinator.group', compact('group', 'attendances', 'avgWeight', 'totalVisits', 'todayVisits', 'stats'));
    }

    public function updateMaintenanceWeight(Group $group, Request $request)
    {
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
        $colors = ['#09cda6', '#3b82f6', '#8b5cf6', '#6366f1', '#f43f5e', '#f59e0b', '#06b6d4', '#10b981'];

        $attendances = $group->attendances()
            ->with(['user', 'weightRecord'])
            ->whereDate('attended_at', today())
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
            ]);

        $patients = $group->patients()->get()->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'initials' => collect(explode(' ', $p->name))->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->join(''),
            'color' => $colors[$p->id % count($colors)],
            'avatar_url' => $p->avatar ? secure_asset('storage/'.$p->avatar) : null,
            'joined_at' => $p->pivot->joined_at ? Carbon::parse($p->pivot->joined_at)->format('d/m/Y H:i') : null,
            'join_source' => $p->pivot->join_source,
        ]);

        return response()->json([
            'count' => $attendances->count(),
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
        $type = $group->recurrence_type ?? 'none';
        $isRecurring = $type !== 'none';
        $tz = 'America/Argentina/Buenos_Aires';

        if ($group->active) {
            $updates = ['active' => false, 'ended_at' => now()];
            if ($isRecurring) {
                $updates['recurrence_end_date'] = Carbon::now($tz)->subDay()->startOfDay()->toDateString();
            }
            $group->update($updates);

            return back()->with('success', $isRecurring ? 'Programa finalizado.' : 'Grupo finalizado.');
        }

        if ($isRecurring && $group->isProgramVigente()) {
            $group->update([
                'recurrence_end_date' => Carbon::now($tz)->subDay()->startOfDay()->toDateString(),
                'ended_at' => $group->ended_at ?? now(),
            ]);

            return back()->with('success', 'Programa finalizado.');
        }

        if (! $isRecurring && $group->started_at) {
            return back()->with('error', 'Un grupo finalizado no puede volver a iniciarse.');
        }

        if (! $isRecurring && ! $group->started_at) {
            $group->update(['active' => true, 'started_at' => now()]);

            return back()->with('success', 'Grupo iniciado.');
        }

        return back()->with('error', 'No se pudo actualizar el grupo.');
    }
}
