<?php

namespace App\Console\Commands;

use App\Models\Group;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

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

            $exists = $group->groupSessions()
                ->whereDate('session_date', $tomorrow->toDateString())
                ->exists();

            if ($exists) {
                $this->line("  Omitido (ya existe): {$group->name} — {$tomorrow->toDateString()}");
                $skipped++;
                continue;
            }

            $this->line('  ' . ($isDryRun ? '[dry] ' : '') . "Crear: {$group->name} — sesión del {$tomorrow->format('d/m/Y')}");

            if (!$isDryRun) {
                $group->findOrCreateSessionForDate($tomorrow);
            }

            $created++;
        }

        $this->info("Resultado: {$created} creadas, {$skipped} omitidas.");
        return self::SUCCESS;
    }

    private function shouldCreateSession(Group $group, Carbon $tomorrow): bool
    {
        $type     = $group->recurrence_type ?? 'none';
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
                $days = $group->meeting_days ?? ($group->meeting_day ? [$group->meeting_day] : []);
                if (empty($days)) return false;
                $dayNums = array_values(array_filter(
                    array_map(fn($d) => self::DAY_MAP[$d] ?? null, $days),
                    fn($d) => $d !== null
                ));
                if (!in_array($tomorrow->dayOfWeek, $dayNums, true)) return false;
                if ($interval === 1) return true;
                return (int)$ref->startOfWeek()->diffInWeeks($tomorrow->copy()->startOfWeek()) % $interval === 0;

            case 'monthly':
                if ((int)$ref->diffInMonths($tomorrow) % $interval !== 0) return false;
                if ($group->monthly_week_ordinal) {
                    return $this->isNthWeekdayOfMonth($group, $tomorrow, (int)$group->monthly_week_ordinal);
                }
                return $tomorrow->day === $ref->day;

            case 'yearly':
                if ($tomorrow->month !== $ref->month || $tomorrow->day !== $ref->day) return false;
                return (int)$ref->diffInYears($tomorrow) % $interval === 0;
        }

        return false;
    }

    /** True cuando $date es la N-ésima ocurrencia (1-4, o 5 = última) del día de semana en meeting_days[0]. */
    private function isNthWeekdayOfMonth(Group $group, Carbon $date, int $ordinal): bool
    {
        $days = $group->meeting_days ?? [];
        $targetDay = self::DAY_MAP[$days[0] ?? ''] ?? null;
        if ($targetDay === null || $date->dayOfWeek !== $targetDay) {
            return false;
        }
        if ($ordinal === 5) {
            return $date->copy()->addDays(7)->month !== $date->month;
        }

        return intdiv($date->day - 1, 7) + 1 === $ordinal;
    }
}
