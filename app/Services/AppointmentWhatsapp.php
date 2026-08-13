<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Envía los WhatsApp automáticos relacionados a turnos (reserva, recordatorio, cancelación).
 * Best-effort: si el paciente no tiene teléfono, la plantilla está desactivada, o WAHA falla,
 * no interrumpe el flujo principal (reservar/cancelar un turno nunca debe fallar por esto).
 */
class AppointmentWhatsapp
{
    public function __construct(private WahaClient $waha)
    {
    }

    public function notifyBooked(Appointment $appointment): void
    {
        $this->send($appointment, 'appointment_booked');
    }

    public function notifyReminder(Appointment $appointment): void
    {
        $this->send($appointment, 'appointment_reminder');
    }

    public function notifyCancelled(Appointment $appointment): void
    {
        $this->send($appointment, 'appointment_cancelled');
    }

    private function send(Appointment $appointment, string $templateKey): void
    {
        $appointment->loadMissing(['patient', 'professional']);
        $patient = $appointment->patient;

        if (! $patient || ! $patient->phone) {
            return;
        }

        $text = WhatsappTemplate::render($templateKey, $this->placeholders($appointment));

        if (! $text) {
            return;
        }

        try {
            $this->waha->sendText($patient->phone, $text);
        } catch (RequestException $e) {
            Log::warning("AppointmentWhatsapp: fallo al enviar '{$templateKey}' para el turno #{$appointment->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function placeholders(Appointment $appointment): array
    {
        $expires = $appointment->starts_at->copy()->addHours(6);

        return [
            'paciente' => $appointment->patient->name,
            'profesional' => $appointment->professional->name ?? '',
            'especialidad' => $appointment->specialty === 'medico' ? 'médico clínico' : 'nutricionista',
            'fecha' => $appointment->starts_at->format('d/m/Y'),
            'hora' => $appointment->starts_at->format('H:i'),
            'link_confirmar' => URL::temporarySignedRoute('turnos.public.confirm', $expires, ['appointment' => $appointment->id]),
            'link_cancelar' => URL::temporarySignedRoute('turnos.public.cancel', $expires, ['appointment' => $appointment->id]),
        ];
    }
}
