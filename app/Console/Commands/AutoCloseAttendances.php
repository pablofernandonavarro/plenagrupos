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

        foreach ($groups as $group) {
            if (! $group->meeting_time || ! $group->session_duration_minutes) {
                continue;
            }

            // Was today a meeting day for this group?
            // We use the same private helper via status: if it was active earlier
            // and the window has now passed, attendances need closing.
            [$h, $m] = array_pad(explode(':', $group->meeting_time), 2, '0');
            $sessionStart = $now->copy()->setTime((int) $h, (int) $m, 0);
            $sessionEnd   = $sessionStart->copy()->addMinutes((int) $group->session_duration_minutes);

            // Only act after the session window has closed and it was a meeting day
            if ($now->lte($sessionEnd)) {
                continue; // window still open
            }

            // Check if today was a scheduled meeting day (use meetsOnDate with sessionStart)
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
