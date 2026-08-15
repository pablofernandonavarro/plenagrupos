<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class StructuredContextBuilder
{
    private const PLAN_LABELS = [
        'descenso' => 'Descenso de peso',
        'mantenimiento' => 'Mantenimiento',
        'mantenimiento_pleno' => 'Mantenimiento Pleno',
    ];

    /**
     * Arma el bloque de datos estructurados (numéricos) de un paciente: perfil, plan,
     * evolución de peso, asistencia, InBody y turnos. No incluye texto libre/notas —
     * eso lo aporta VectorSearchService por separado.
     */
    public function build(User $patient): string
    {
        $records = $patient->weightRecords()->orderBy('recorded_at')->get();
        $attendances = $patient->attendances()->with('group')->orderBy('attended_at')->get();
        $groups = $patient->patientGroups()->get();
        $inbodyRecords = $patient->inbodyRecords()->orderByDesc('test_date')->take(3)->get();
        $appointments = $patient->appointmentsAsPatient()
            ->orderByDesc('starts_at')
            ->take(10)
            ->get();

        $firstW = $records->first()?->weight ?? 'desconocido';
        $lastW = $records->last()?->weight ?? 'desconocido';
        $trend = self::weightTrend($records->values());
        $piso = $patient->peso_piso ?? 'no definido';
        $techo = $patient->peso_techo ?? 'no definido';
        $ideal = $patient->ideal_weight ?? 'no definido';

        $age = $patient->birth_date ? $patient->birth_date->diffInYears(now()).' años' : null;
        $genderLbl = match ($patient->gender) {
            'male' => 'Masculino',
            'female' => 'Femenino',
            'other' => 'Otro',
            default => null,
        };
        $height = $patient->height_cm ? $patient->height_cm.' cm' : null;

        $planLabel = self::PLAN_LABELS[$patient->plan] ?? 'no asignado';
        $faseActualLabel = $patient->fase_actual
            ? self::PLAN_LABELS[$patient->fase_actual] ?? $patient->fase_actual
            : null;
        $hayConflictoPlan = $patient->fase_actual && $patient->fase_actual !== $patient->plan;

        $cycleInfo = '';
        if ($patient->plan_start_date) {
            [$cs, $ce] = $patient->currentPlanCycle();
            $cycleInfo = "Ciclo actual: {$cs->format('d/m/Y')} al {$ce->format('d/m/Y')}";
        }

        $inRange = ($lastW !== 'desconocido' && $piso !== 'no definido' && $techo !== 'no definido')
            ? ((float) $lastW >= (float) $piso && (float) $lastW <= (float) $techo
                ? 'dentro del rango de mantenimiento'
                : ((float) $lastW < (float) $piso ? 'por debajo del rango' : 'por encima del rango'))
            : 'sin rango definido';

        $totalAttendances = $attendances->count();
        $firstAttendance = $attendances->first()?->attended_at?->format('d/m/Y') ?? 'sin registro';
        $lastAttendance = $attendances->last()?->attended_at?->format('d/m/Y') ?? 'sin registro';

        $groupsSummary = $groups->map(fn ($g) => "  · {$g->name} (tipo: ".($g->group_type ?? 'descenso').')')
            ->join("\n");

        $byType = $attendances->groupBy(fn ($a) => $a->group?->group_type ?? 'descenso')
            ->map(fn ($g) => $g->count());
        $attendanceByType = $byType->map(fn ($c, $t) => '  · '.(self::PLAN_LABELS[$t] ?? $t).": {$c} asistencias")
            ->join("\n");

        $weightHistory = $records->sortByDesc('recorded_at')->take(15)
            ->map(fn ($r) => "  [{$r->recorded_at->format('d/m/Y')}] {$r->weight} kg".
                ($r->group ? " ({$r->group->name})" : ''))
            ->join("\n");

        $inbodySection = '';
        if ($inbodyRecords->isNotEmpty()) {
            $inbodyLines = $inbodyRecords->map(function ($r) {
                $parts = ["  [{$r->test_date->format('d/m/Y')}]"];
                if ($r->weight) {
                    $parts[] = "Peso: {$r->weight} kg";
                }
                if ($r->body_fat_percentage) {
                    $parts[] = "Grasa: {$r->body_fat_percentage}%";
                }
                if ($r->skeletal_muscle_mass) {
                    $parts[] = "Músculo: {$r->skeletal_muscle_mass} kg";
                }
                if ($r->visceral_fat_level) {
                    $parts[] = "Visceral: {$r->visceral_fat_level}";
                }
                if ($r->bmi) {
                    $parts[] = "IMC: {$r->bmi}";
                }
                if ($r->inbody_score) {
                    $parts[] = "Score InBody: {$r->inbody_score}/100";
                }

                return implode(' | ', $parts);
            })->join("\n");
            $inbodySection = "\n=== ESTUDIOS INBODY (últimos 3) ===\n{$inbodyLines}\n";
        }

        $appointmentsSection = '';
        if ($appointments->isNotEmpty()) {
            $now = now();
            $appointmentLines = $appointments->map(function ($a) use ($now) {
                $specialtyLbl = $a->specialty === 'medico' ? 'Médico clínico' : 'Nutricionista';
                $when = $a->starts_at->format('d/m/Y H:i').($a->starts_at->isFuture() ? ' (próximo)' : '');
                $statusLbl = match ($a->status) {
                    'confirmed' => 'confirmado',
                    'pending' => 'pendiente de confirmación',
                    'cancelled' => 'cancelado',
                    'completed' => 'cumplido',
                    default => $a->status,
                };

                return "  [{$when}] {$specialtyLbl} — estado: {$statusLbl}";
            })->join("\n");
            $appointmentsSection = "\n=== TURNOS (últimos 10) ===\n{$appointmentLines}\n";
        }

        $out = "=== PERFIL DEL PACIENTE ===\n".
            ($age ? "- Edad: {$age}\n" : '').
            ($genderLbl ? "- Género: {$genderLbl}\n" : '').
            ($height ? "- Altura: {$height}\n" : '').
            "- Plan contratado: {$planLabel}\n".
            ($faseActualLabel ? "- Fase clínica actual: {$faseActualLabel}".($hayConflictoPlan ? ' (DISTINTA al plan contratado)' : '')."\n" : '').
            ($cycleInfo ? "- {$cycleInfo}\n" : '').
            "- Peso inicial: {$firstW} kg\n".
            "- Peso actual: {$lastW} kg\n".
            '- Tendencia: '.round($trend, 3)." kg/sesión (negativo = pérdida)\n".
            "- Peso ideal: {$ideal} kg\n".
            "- Rango de mantenimiento: {$piso} – {$techo} kg\n".
            "- Estado actual respecto al rango: {$inRange}\n\n".
            "=== ASISTENCIA ===\n".
            "- Total de asistencias: {$totalAttendances}\n".
            "- Primera asistencia: {$firstAttendance}\n".
            "- Última asistencia: {$lastAttendance}\n".
            ($groupsSummary ? "- Grupos:\n{$groupsSummary}\n" : '').
            ($attendanceByType ? "- Por tipo de grupo:\n{$attendanceByType}\n" : '')."\n".
            "=== HISTORIAL DE PESO (últimos 15 registros) ===\n".
            ($weightHistory ?: '  Sin registros de peso.')."\n".
            $inbodySection.
            $appointmentsSection;

        return $out;
    }

    private static function weightTrend(Collection $records): float
    {
        $n = $records->count();
        if ($n < 2) {
            return 0;
        }

        $x = range(0, $n - 1);
        $y = $records->pluck('weight')->map(fn ($w) => (float) $w)->toArray();
        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;
        $num = 0;
        $den = 0;
        foreach ($x as $i => $xi) {
            $num += ($xi - $meanX) * ($y[$i] - $meanY);
            $den += ($xi - $meanX) ** 2;
        }

        return $den > 0 ? round($num / $den, 3) : 0;
    }
}
