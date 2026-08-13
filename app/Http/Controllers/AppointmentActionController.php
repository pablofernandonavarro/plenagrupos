<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\AppointmentWhatsapp;

class AppointmentActionController extends Controller
{
    /** GET: solo muestra la pantalla con el botón. No cambia el turno. */
    public function confirmShow(Appointment $appointment)
    {
        $appointment->loadMissing(['patient', 'professional']);

        if ($appointment->status !== 'pending') {
            return view('turnos.confirmado', compact('appointment'));
        }

        return view('turnos.confirmar', compact('appointment'));
    }

    /** POST: acá sí se confirma, solo cuando el paciente toca el botón. */
    public function confirm(Appointment $appointment)
    {
        $appointment->loadMissing(['patient', 'professional']);

        if ($appointment->status === 'pending') {
            $appointment->update(['status' => 'confirmed']);
        }

        return view('turnos.confirmado', compact('appointment'));
    }

    /** GET: solo muestra la pantalla con el botón. No cambia el turno. */
    public function cancelShow(Appointment $appointment)
    {
        $appointment->loadMissing(['patient', 'professional']);

        if (in_array($appointment->status, ['cancelled', 'completed'], true)) {
            return view('turnos.cancelado', compact('appointment'));
        }

        return view('turnos.cancelar', compact('appointment'));
    }

    /** POST: acá sí se cancela, solo cuando el paciente toca el botón. */
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
