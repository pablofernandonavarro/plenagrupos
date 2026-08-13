<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\AppointmentWhatsapp;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendAppointmentReminders extends Command
{
    protected $signature   = 'turnos:enviar-recordatorios {--dry-run : Muestra qué se enviaría sin enviar nada}';
    protected $description = 'Envía por WhatsApp el recordatorio de los turnos médicos/nutricionista de mañana';

    public function handle(AppointmentWhatsapp $notifier): int
    {
        $tz = 'America/Argentina/Buenos_Aires';
        $tomorrow = Carbon::tomorrow($tz);
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) $this->warn('-- DRY RUN: no se enviará nada --');

        $appointments = Appointment::with(['patient', 'professional'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNull('reminded_at')
            ->whereBetween('starts_at', [$tomorrow->copy()->startOfDay(), $tomorrow->copy()->endOfDay()])
            ->get();

        $sent = 0;

        foreach ($appointments as $appointment) {
            $this->line('  ' . ($isDryRun ? '[dry] ' : '') . "Recordatorio: {$appointment->patient?->name} — {$appointment->starts_at->format('d/m/Y H:i')}");

            if (! $isDryRun) {
                $notifier->notifyReminder($appointment);
                $appointment->update(['reminded_at' => now()]);
            }

            $sent++;
        }

        $this->info("Resultado: {$sent} recordatorio(s) " . ($isDryRun ? 'a enviar' : 'enviados') . '.');
        return self::SUCCESS;
    }
}
