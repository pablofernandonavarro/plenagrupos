<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\GroupAttendance;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoCloseAttendances extends Command
{
    protected $signature   = 'attendances:auto-close';
    protected $description = 'Close open attendances for sessions whose time window has ended.';

    public function handle(): void
    {
        $tz  = 'America/Argentina/Buenos_Aires';
        $now = Carbon::now($tz);
        $closed = 0;

        // 1. Orphaned records from previous days — close at attended_at + duration
        $stale = GroupAttendance::whereNull('left_at')
            ->whereDate('attended_at', '<', $now->toDateString())
            ->with('group')
            ->get();

        foreach ($stale as $att) {
            $duration = (int) ($att->group?->session_duration_minutes ?? 120);
            $att->update(['left_at' => $att->attended_at->copy()->addMinutes($duration)]);
            $closed++;
        }

        // 2. Today's open attendances for recurring groups whose window has passed
        $groups = Group::whereNotIn('recurrence_type', ['none'])
            ->where(function ($q) use ($now) {
                $q->whereNull('recurrence_end_date')
                  ->orWhere('recurrence_end_date', '>=', $now->toDateString());
            })
            ->whereNotNull('meeting_time')
            ->get();

        foreach ($groups as $group) {
            [$h, $m] = array_pad(explode(':', $group->meeting_time), 2, '0');
            $sessionStart = $now->copy()->setTime((int) $h, (int) $m, 0);
            $duration     = (int) ($group->session_duration_minutes ?? 120);
            $sessionEnd   = $sessionStart->copy()->addMinutes($duration);

            // Session window hasn't closed yet — nothing to do
            if ($now->lte($sessionEnd)) {
                continue;
            }

            // Not a meeting day today
            if (! $group->meetsOnDate($sessionStart)) {
                continue;
            }

            $count = GroupAttendance::where('group_id', $group->id)
                ->whereDate('attended_at', $now->toDateString())
                ->whereNull('left_at')
                ->update(['left_at' => $sessionEnd]);

            $closed += $count;
        }

        // 3. Groups manually closed today by coordinator/admin — close remaining open attendances
        $manuallyClosed = Group::whereNotNull('ended_at')
            ->whereDate('ended_at', $now->toDateString())
            ->get();

        foreach ($manuallyClosed as $group) {
            $endedAt = Carbon::parse($group->getRawOriginal('ended_at'))->timezone($tz);

            $count = GroupAttendance::where('group_id', $group->id)
                ->whereDate('attended_at', $now->toDateString())
                ->whereNull('left_at')
                ->update(['left_at' => $endedAt]);

            $closed += $count;
        }

        $this->info("Auto-closed {$closed} open attendance(s).");
    }
}
