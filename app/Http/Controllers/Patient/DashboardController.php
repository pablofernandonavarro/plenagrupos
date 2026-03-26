<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupAttendance;
use App\Models\GroupMembershipLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $weightRecords = $user->weightRecords()
            ->with('group')
            ->latest('recorded_at')
            ->get();

        $latestWeight  = $weightRecords->first()?->weight;
        $initialWeight = $weightRecords->last()?->weight;
        $totalLoss     = ($initialWeight && $latestWeight) ? round($initialWeight - $latestWeight, 2) : null;

        // Attendance stats per group: session count + total minutes (only closed attendances)
        $attendanceStats = GroupAttendance::where('user_id', $user->id)
            ->whereNotNull('left_at')
            ->get()
            ->groupBy('group_id')
            ->map(fn ($atts) => [
                'sessions' => $atts->count(),
                'minutes'  => $atts->sum(fn ($a) => (int) $a->attended_at->diffInMinutes($a->left_at)),
            ]);

        // get groups where patient has at least one attendance (open or closed)
        $attendedGroupIds = GroupAttendance::where('user_id', $user->id)
            ->distinct()->pluck('group_id');
        $groups = Group::whereIn('id', $attendedGroupIds)->get();

        // Today's attendance per group (to show personal check-in/out status)
        $tz = 'America/Argentina/Buenos_Aires';
        $todayDate = \Carbon\Carbon::now($tz)->toDateString();
        $todayAttendances = GroupAttendance::where('user_id', $user->id)
            ->whereDate('attended_at', $todayDate)
            ->latest('attended_at')
            ->get()
            ->keyBy('group_id');

        $enrolledGroupIds = $user->patientGroups()->wherePivot('left_at', null)->pluck('groups.id');

        $sessionHistory = GroupAttendance::where('user_id', $user->id)
            ->with(['group', 'groupSession'])
            ->latest('attended_at')
            ->get()
            ->map(fn ($a) => (object) [
                'group_name'  => $a->group?->name ?? '(grupo eliminado)',
                'date'        => $a->attended_at->format('d/m/Y'),
                'time'        => $a->attended_at->format('H:i'),
                'session_num' => $a->groupSession?->sequence_number,
                'minutes'     => $a->left_at ? (int) $a->attended_at->diffInMinutes($a->left_at) : null,
            ]);

        // Chart data (chronological)
        $chartRecords = $weightRecords->sortBy('recorded_at')->values();

        // Linear regression trend (kg per session, negative = losing)
        $trend = 0;
        $n = $chartRecords->count();
        if ($n >= 2) {
            $x = range(0, $n - 1);
            $y = $chartRecords->pluck('weight')->map(fn($w) => (float) $w)->toArray();
            $meanX = array_sum($x) / $n;
            $meanY = array_sum($y) / $n;
            $num = 0; $den = 0;
            foreach ($x as $i => $xi) {
                $num += ($xi - $meanX) * ($y[$i] - $meanY);
                $den += ($xi - $meanX) ** 2;
            }
            $trend = $den > 0 ? round($num / $den, 3) : 0;
        }

        // Progress toward ideal weight (0–100%)
        $progressPct = null;
        if ($initialWeight && $user->ideal_weight && (float)$initialWeight !== (float)$user->ideal_weight) {
            $totalNeeded = (float)$initialWeight - (float)$user->ideal_weight;
            $achieved    = (float)$initialWeight - (float)$latestWeight;
            $progressPct = $totalNeeded != 0
                ? max(0, min(100, round($achieved / $totalNeeded * 100)))
                : null;
        }

        $piso  = $user->peso_piso;
        $techo = $user->peso_techo;
        $inRange = ($latestWeight && $piso && $techo)
            ? ((float)$latestWeight >= (float)$piso && (float)$latestWeight <= (float)$techo)
            : null;

        $chartData = [
            'labels'  => $chartRecords->map(fn($r) => $r->recorded_at->format('d/m'))->toArray(),
            'weights' => $chartRecords->map(fn($r) => (float) $r->weight)->toArray(),
            'piso'    => $piso  ? (float) $piso  : null,
            'techo'   => $techo ? (float) $techo : null,
        ];

        return view('patient.dashboard', compact(
            'weightRecords', 'latestWeight', 'totalLoss', 'groups', 'sessionHistory',
            'trend', 'progressPct', 'inRange', 'chartData', 'piso', 'techo', 'enrolledGroupIds',
            'attendanceStats', 'todayAttendances'
        ));
    }

    public function profile()
    {
        $user = auth()->user();

        $weightRecords = $user->weightRecords()
            ->with('group')
            ->latest('recorded_at')
            ->get();

        $availableGroups = Group::orderBy('name')->get();

        return view('patient.profile', compact('weightRecords', 'availableGroups'));
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'peso_piso'          => 'nullable|numeric|min:0|max:300',
            'peso_techo'         => 'nullable|numeric|min:0|max:300',
            'avatar'             => 'nullable|image|max:2048',
            'belonging_group_id' => 'nullable|exists:groups,id',
        ]);

        $user = auth()->user();

        $user->peso_piso  = $data['peso_piso'] ?? null;
        $user->peso_techo = $data['peso_techo'] ?? null;

        if ($request->has('belonging_group_id')) {
            $user->belonging_group_id = $data['belonging_group_id'] ?? null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        return back()->with('success', 'Perfil actualizado.');
    }

    public function leaveGroup(Request $request, Group $group)
    {
        $user = auth()->user();

        $pivot = $user->patientGroups()
            ->wherePivot('left_at', null)
            ->find($group->id);

        if (! $pivot) {
            return back()->with('error', 'No estás activo en ese grupo.');
        }

        $now = now();

        $user->patientGroups()->updateExistingPivot($group->id, ['left_at' => $now]);

        // Close the open log entry
        GroupMembershipLog::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->latest('joined_at')
            ->first()
            ?->update(['left_at' => $now]);

        // Mark exit on today's attendance if not already marked
        GroupAttendance::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->whereDate('attended_at', today())
            ->whereNull('left_at')
            ->latest('attended_at')
            ->first()
            ?->update(['left_at' => $now]);

        return back()->with('success', 'Saliste del grupo "' . $group->name . '".');
    }
}
