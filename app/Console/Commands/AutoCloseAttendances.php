<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\GroupAttendance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // 4. Auto-close the group session state for groups whose window has ended
        //    but were never manually closed (active flag / started_at still set).

        // 4a. Non-recurring groups: active=true and started_at + duration has passed
        $openManual = Group::where('recurrence_type', 'none')
            ->where('active', true)
            ->whereNotNull('started_at')
            ->get();

        foreach ($openManual as $group) {
            $duration   = (int) ($group->session_duration_minutes ?? 120);
            $autoCloseAt = Carbon::parse($group->getRawOriginal('started_at'))
                ->timezone($tz)
                ->addMinutes($duration);

            if ($now->gt($autoCloseAt)) {
                $group->update(['active' => false, 'ended_at' => $autoCloseAt]);

                // Close all open attendances
                $count = GroupAttendance::where('group_id', $group->id)
                    ->whereNull('left_at')
                    ->update(['left_at' => $autoCloseAt]);

                $closed += $count;

                // Sacar a todos los pacientes del grupo finalizado
                DB::table('group_patient')
                    ->where('group_id', $group->id)
                    ->whereNull('left_at')
                    ->update(['left_at' => $autoCloseAt]);
            }
        }

        // 4b. Recurring groups manually opened today (started_at = today, ended_at null)
        //     but whose scheduled window has ended
        $openRecurring = Group::whereNotIn('recurrence_type', ['none'])
            ->whereNotNull('started_at')
            ->whereDate('started_at', $now->toDateString())
            ->whereNull('ended_at')
            ->whereNotNull('meeting_time')
            ->get();

        foreach ($openRecurring as $group) {
            [$h, $m]  = array_pad(explode(':', $group->meeting_time), 2, '0');
            $duration = (int) ($group->session_duration_minutes ?? 120);
            $sessionEnd = $now->copy()->setTime((int) $h, (int) $m, 0)->addMinutes($duration);

            if ($now->gt($sessionEnd)) {
                $group->update(['ended_at' => $sessionEnd, 'started_at' => null]);

                // Close all open attendances for today
                $count = GroupAttendance::where('group_id', $group->id)
                    ->whereDate('attended_at', $now->toDateString())
                    ->whereNull('left_at')
                    ->update(['left_at' => $sessionEnd]);

                $closed += $count;
            }
        }

        // 4c. Recurring groups with a stale started_at from a previous day (coordinator
        //     never closed and never came back) — clear the flag so they reset cleanly
        Group::whereNotIn('recurrence_type', ['none'])
            ->whereNotNull('started_at')
            ->whereDate('started_at', '<', $now->toDateString())
            ->whereNull('ended_at')
            ->update(['started_at' => null]);

        // 5. Grupos recurrentes que alcanzaron su recurrence_end_date — sacar pacientes
        $expiredRecurring = Group::whereNotIn('recurrence_type', ['none'])
            ->whereNotNull('recurrence_end_date')
            ->where('recurrence_end_date', '<', $now->toDateString())
            ->get();

        foreach ($expiredRecurring as $group) {
            // Sacar a todos los pacientes del grupo que alcanzó su fecha de fin
            DB::table('group_patient')
                ->where('group_id', $group->id)
                ->whereNull('left_at')
                ->update(['left_at' => Carbon::parse($group->recurrence_end_date)->endOfDay()]);
        }

        $this->info("Auto-closed {$closed} open attendance(s).");
        Log::info("attendances:auto-close ran — closed {$closed} attendance(s).");
    }
}
