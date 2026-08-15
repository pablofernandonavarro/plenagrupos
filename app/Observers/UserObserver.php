<?php

namespace App\Observers;

use App\Models\User;
use App\Services\EmbeddingSyncService;

class UserObserver
{
    public function __construct(private EmbeddingSyncService $sync)
    {
    }

    public function created(User $user): void
    {
        if (! $user->isPatient()) {
            return;
        }

        $this->sync->sync('personal_goal', $user->id, $user->id, $user->personal_goal);
        $this->sync->sync('patient_status_note', $user->id, $user->id, $user->patient_status_note);
    }

    public function updated(User $user): void
    {
        if (! $user->isPatient()) {
            return;
        }

        if ($user->wasChanged('personal_goal')) {
            $this->sync->sync('personal_goal', $user->id, $user->id, $user->personal_goal);
        }

        if ($user->wasChanged('patient_status_note')) {
            $this->sync->sync('patient_status_note', $user->id, $user->id, $user->patient_status_note);
        }
    }

    public function deleted(User $user): void
    {
        $this->sync->sync('personal_goal', $user->id, $user->id, null);
        $this->sync->sync('patient_status_note', $user->id, $user->id, null);
    }
}
