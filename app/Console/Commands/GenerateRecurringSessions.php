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
        'Domingo'   => 0, 'Lunes'     => 1, 'Martes'    => 2,
        'Miércoles' => 3, 'Jueves'    => 4, 'Viernes'   => 5, 'Sábado'    => 6,
    ];

    public function handle(): int
    {
        $tomorrow = Carbon::tomorrow('America/Argentina/Buenos_Aires');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) $this->warn('-- DRY RUN: no se guardará nada --');

        $groups = Group::where('active', true)
            ->whereNotIn('recurrence_type', ['none'])
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($groups as $group) {
            if (!$this->shouldCreateSession($group, $tomorrow)) continue;

            $exists = TherapeuticSession::where('group_id', $group->id)
                ->whereDate('session_date', $tomorrow->toDateString())
                ->exists();

            if ($exists) {
                $this->line("  Omitido (ya existe): {$group->name} — {$tomorrow->toDateString()}");
                $skipped++;
                continue;
            }

            $timeLabel   = $group->meeting_time ? ' ' . substr($group->meeting_time, 0, 5) : '';
            $sessionName = 'Sesión ' . $tomorrow->format('d/m/Y') . $timeLabel;

            $this->line('  ' . ($isDryRun ? '[dry] ' : '') . "Crear: {$group->name} — {$sessionName}");

            if (!$isDryRun) {
                TherapeuticSession::create([
                    'group_id'     => $group->id,
                    'name'         => $sessionName,
                    'session_date' => $tomorrow->toDateString(),
                    'qr_token'     => Str::uuid(),
                    'status'       => 'active',
                    'created_by'   => $group->admin_id,
                ]);
            }

            $created++;
        }

        $this->info("Resultado: {$created} creadas, {$skipped} omitidas.");
        return self::SUCCESS;
    }

    private function shouldCreateSession(Group $group, Carbon $tomorrow): bool
    {
        $type     = $group->attributes['recurrence_type'] ?? 'none';
        $interval = max(1, (int)($group->recurrence_interval ?? 1));

        // Check end date
        if ($group->recurrence_end_date && $tomorrow->gt($group->recurrence_end_date)) {
            return false;
        }

        // Reference date for interval calculations
        $ref = ($group->started_at ?? $group->created_at)->copy()->startOfDay();

        switch ($type) {
            case 'daily':
                return (int)$ref->diffInDays($tomorrow) % $interval === 0;

            case 'weekly':
                $days = $group->meeting_days ?? ($group->attributes['meeting_day'] ? [$group->attributes['meeting_day']] : []);
                if (empty($days)) return false;
                $dayNums = array_values(array_filter(
                    array_map(fn($d) => self::DAY_MAP[$d] ?? null, $days),
                    fn($d) => $d !== null
                ));
                if (!in_array($tomorrow->dayOfWeek, $dayNums, true)) return false;
                if ($interval === 1) return true;
                return (int)$ref->startOfWeek()->diffInWeeks($tomorrow->copy()->startOfWeek()) % $interval === 0;

            case 'monthly':
                if ($tomorrow->day !== $ref->day) return false;
                return (int)$ref->diffInMonths($tomorrow) % $interval === 0;

            case 'yearly':
                if ($tomorrow->month !== $ref->month || $tomorrow->day !== $ref->day) return false;
                return (int)$ref->diffInYears($tomorrow) % $interval === 0;
        }

        return false;
    }
}
