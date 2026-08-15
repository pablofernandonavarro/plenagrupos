<?php

namespace App\Observers;

use App\Models\GroupAttendance;
use App\Services\EmbeddingSyncService;

class GroupAttendanceObserver
{
    public function __construct(private EmbeddingSyncService $sync)
    {
    }

    public function created(GroupAttendance $attendance): void
    {
        $this->sync->sync('coordinator_note', $attendance->user_id, $attendance->id, $attendance->coordinator_notes);
    }

    public function updated(GroupAttendance $attendance): void
    {
        if ($attendance->wasChanged('coordinator_notes')) {
            $this->sync->sync('coordinator_note', $attendance->user_id, $attendance->id, $attendance->coordinator_notes);
        }
    }

    public function deleted(GroupAttendance $attendance): void
    {
        $this->sync->sync('coordinator_note', $attendance->user_id, $attendance->id, null);
    }
}
