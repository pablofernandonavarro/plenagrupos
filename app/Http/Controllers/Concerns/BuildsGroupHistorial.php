<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Group;
use App\Models\GroupAttendance;
use App\Models\GroupMembershipLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait BuildsGroupHistorial
{
    /**
     * @param  Collection<int, GroupAttendance>  $attendances
     * @return array{inRange: int, above: int, below: int, noWeight: int}
     */
    protected function weightRangeStats(Collection $attendances): array
    {
        $inRange = 0;
        $above = 0;
        $below = 0;
        $noWeight = 0;
        foreach ($attendances as $a) {
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

        return compact('inRange', 'above', 'below', 'noWeight');
    }

    /**
     * @return array{
     *     historyDates: Collection,
     *     historialDate: string|null,
     *     historialStats: array|null,
     *     historialAttendances: \Illuminate\Database\Eloquent\Collection|null,
     *     historialMembershipEvents: \Illuminate\Database\Eloquent\Collection|null
     * }
     */
    protected function buildGroupHistorialData(Group $group, Request $request): array
    {
        $historyDates = $group->attendances()
            ->orderByDesc('attended_at')
            ->get(['attended_at'])
            ->map(fn ($a) => $a->attended_at->format('Y-m-d'))
            ->unique()
            ->sort()
            ->reverse()
            ->values();

        $historialRaw = $request->query('historial');
        $historialDate = null;
        if (is_string($historialRaw) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $historialRaw)) {
            try {
                $parsed = Carbon::createFromFormat('Y-m-d', $historialRaw)->format('Y-m-d');
                if ($historyDates->contains($parsed)) {
                    $historialDate = $parsed;
                }
            } catch (\Throwable) {
            }
        }

        $historialStats = null;
        $historialAttendances = null;
        $historialMembershipEvents = null;

        if ($historialDate !== null) {
            $day = Carbon::parse($historialDate)->startOfDay();
            $historialAttendances = $group->attendances()
                ->with(['user', 'weightRecord'])
                ->whereDate('attended_at', $day)
                ->orderBy('attended_at')
                ->get();
            $historialStats = $this->weightRangeStats($historialAttendances);
            $historialMembershipEvents = GroupMembershipLog::query()
                ->where('group_id', $group->id)
                ->with('user')
                ->where(function ($q) use ($day) {
                    $q->whereDate('joined_at', $day)
                        ->orWhereDate('left_at', $day);
                })
                ->orderBy('joined_at')
                ->get();
        }

        return compact('historyDates', 'historialDate', 'historialStats', 'historialAttendances', 'historialMembershipEvents');
    }
}
