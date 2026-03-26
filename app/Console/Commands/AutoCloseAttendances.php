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

        // Only recurring groups that are still vigente (not permanently closed)
        $groups = Group::whereNotIn('recurrence_type', ['none'])
            ->whereNull('recurrence_end_date')
            ->orWhere('recurrence_end_date', '>=', $now->toDateString())
            ->get();

        $closed = 0;

        // First: close any stale open attendances from PREVIOUS days (orphaned records).
        // Use each group's configured session_duration_minutes as the session length.
        $stale = GroupAttendance::whereNull('left_at')
            ->whereDate('attended_at', '<', $now->toDateString())
            ->with('group')
            ->get();

        foreach ($stale as $attendance) {
            $duration = (int) ($attendance->group?->session_duration_minutes ?? 90);
            $attendance->update(['left_at' => $attendance->attended_at->copy()->addMinutes($duration)]);
            $closed++;
        }

        foreach ($groups as $group) {
            if (! $group->meeting_time || ! $group->session_duration_minutes) {
                continue;
            }

            [$h, $m] = array_pad(explode(':', $group->meeting_time), 2, '0');
            $sessionStart = $now->copy()->setTime((int) $h, (int) $m, 0);
            $sessionEnd   = $sessionStart->copy()->addMinutes((int) $group->session_duration_minutes);

            // Only act after the session window has closed and it was a meeting day
            if ($now->lte($sessionEnd)) {
                continue;
            }

            if (! $group->meetsOnDate($sessionStart)) {
                continue;
            }

            // Close all open attendances for today
            $count = GroupAttendance::where('group_id', $group->id)
                ->whereDate('attended_at', $now->toDateString())
                ->whereNull('left_at')
                ->update(['left_at' => $sessionEnd]);

            $closed += $count;
        }

        $this->info("Auto-closed {$closed} open attendance(s).");
    }
}
