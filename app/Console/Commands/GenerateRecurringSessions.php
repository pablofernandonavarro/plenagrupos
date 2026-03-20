<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\TherapeuticSession;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class GenerateRecurringSessions extends Command
{
    protected $signature   = 'sessions:generate-recurring {--dry-run : Muestra qué se crearía sin guardar}';
    protected $description = 'Crea automáticamente las sesiones del día siguiente para los grupos recurrentes';

    private const DAY_MAP = [
        'Domingo'   => 0,
        'Lunes'     => 1,
        'Martes'    => 2,
        'Miércoles' => 3,
        'Jueves'    => 4,
        'Viernes'   => 5,
        'Sábado'    => 6,
    ];

    public function handle(): int
    {
        $tomorrow  = Carbon::tomorrow('America/Argentina/Buenos_Aires');
        $dayOfWeek = $tomorrow->dayOfWeek;
        $isDryRun  = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('-- DRY RUN: no se guardará nada --');
        }

        $groups = Group::where('active', true)
            ->where('auto_sessions', true)
            ->whereNotNull('meeting_day')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($groups as $group) {
            $groupDayNumber = self::DAY_MAP[$group->meeting_day] ?? null;

            if ($groupDayNumber === null || $groupDayNumber !== $dayOfWeek) {
                continue;
            }

            $exists = TherapeuticSession::where('group_id', $group->id)
                ->whereDate('session_date', $tomorrow->toDateString())
                ->exists();

            if ($exists) {
                $this->line("  Omitido (ya existe): {$group->name} — {$tomorrow->toDateString()}");
                $skipped++;
                continue;
            }

            $timeLabel = $group->meeting_time ? ' ' . substr($group->meeting_time, 0, 5) : '';
            $sessionName = 'Sesión ' . $tomorrow->format('d/m/Y') . $timeLabel;

            $this->line("  " . ($isDryRun ? '[dry] ' : '') . "Crear: {$group->name} — {$sessionName}");

            if (!$isDryRun) {
                TherapeuticSession::create([
                    'group_id'    => $group->id,
                    'name'        => $sessionName,
                    'session_date' => $tomorrow->toDateString(),
                    'qr_token'    => Str::uuid(),
                    'status'      => 'active',
                    'created_by'  => $group->admin_id,
                ]);
            }

            $created++;
        }

        $this->info("Resultado: {$created} creadas, {$skipped} omitidas.");

        return self::SUCCESS;
    }
}
