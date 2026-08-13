<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\AppointmentWhatsapp;

class AppointmentActionController extends Controller
{
    public function confirm(Appointment $appointment)
    {
        $appointment->loadMissing(['patient', 'professional']);

        if ($appointment->status === 'pending') {
            $appointment->update(['status' => 'confirmed']);
        }

        return view('turnos.confirmado', compact('appointment'));
    }

    public function cancel(Appointment $appointment, AppointmentWhatsapp $notifier)
    {
        $appointment->loadMissing(['patient', 'professional']);

        if (! in_array($appointment->status, ['cancelled', 'completed'], true)) {
            $appointment->update(['status' => 'cancelled']);
            $notifier->notifyCancelled($appointment);
        }

        return view('turnos.cancelado', compact('appointment'));
    }
}
