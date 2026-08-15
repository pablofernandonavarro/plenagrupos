<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\EmbeddingSyncService;

class AppointmentObserver
{
    public function __construct(private EmbeddingSyncService $sync)
    {
    }

    public function created(Appointment $appointment): void
    {
        $this->sync->sync('appointment_note', $appointment->patient_id, $appointment->id, $appointment->notes);
    }

    public function updated(Appointment $appointment): void
    {
        if ($appointment->wasChanged('notes')) {
            $this->sync->sync('appointment_note', $appointment->patient_id, $appointment->id, $appointment->notes);
        }
    }

    public function deleted(Appointment $appointment): void
    {
        $this->sync->sync('appointment_note', $appointment->patient_id, $appointment->id, null);
    }
}
