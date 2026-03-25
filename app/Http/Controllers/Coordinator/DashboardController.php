<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Group::whereHas('coordinators', fn ($q) => $q->where('users.id', auth()->id()))
            ->with(['patients'])
            ->withCount(['attendances', 'weightRecords'])
            ->orderBy('created_at', 'desc');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $collection = $query->get();

        $status = $request->input('status', '');
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

        return view('coordinator.dashboard', ['groups' => $groups]);
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
        $attendances = $group->attendances()
            ->with(['user', 'weightRecord'])
            ->latest('attended_at')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->user_id,
                'name' => $a->user->name,
                'attended_at' => $a->attended_at->format('H:i'),
                'weight' => $a->weightRecord?->weight,
            ]);

        return response()->json([
            'count' => $attendances->count(),
            'attendances' => $attendances,
        ]);
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
        if (! $group->active && $group->started_at) {
            return back()->with('error', 'Un grupo finalizado no puede volver a iniciarse.');
        }

        if ($group->active) {
            $group->update(['active' => false, 'ended_at' => now()]);

            return back()->with('success', 'Grupo finalizado.');
        }

        $group->update(['active' => true, 'started_at' => now()]);

        return back()->with('success', 'Grupo iniciado.');
    }
}
