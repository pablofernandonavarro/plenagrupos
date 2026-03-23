<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $query = Group::with(['patients'])
            ->withCount(['attendances', 'weightRecords'])
            ->orderBy('created_at', 'desc');

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

        $groups = $query->paginate(10)->withQueryString();
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

        $inRange = 0; $above = 0; $below = 0; $noWeight = 0;
        foreach ($todayAttendances as $a) {
            $rw    = $a->weightRecord?->weight;
            $piso  = $a->user->peso_piso;
            $techo = $a->user->peso_techo;
            if (!$rw) { $noWeight++; }
            elseif ($techo && $rw > $techo) { $above++; }
            elseif ($piso && $rw < $piso) { $below++; }
            elseif ($piso || $techo) { $inRange++; }
            else { $noWeight++; }
        }

        $todayVisits = $todayAttendances->count();
        $stats = compact('inRange', 'above', 'below', 'noWeight');

        return view('coordinator.group', compact('group', 'attendances', 'avgWeight', 'totalVisits', 'todayVisits', 'stats'));
    }

    public function updateMaintenanceWeight(Group $group, \Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'user_id'            => 'required|exists:users,id',
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

    public function profile()
    {
        return view('coordinator.profile');
    }

    public function updateProfile(\Illuminate\Http\Request $request)
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
        if (!$group->active && $group->started_at) {
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
