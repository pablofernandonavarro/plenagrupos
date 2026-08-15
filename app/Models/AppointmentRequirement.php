<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentRequirement extends Model
{
    protected $fillable = ['patient_plan', 'specialty', 'monthly_required_count', 'cycle_days'];

    protected $casts = [
        'monthly_required_count' => 'integer',
        'cycle_days' => 'integer',
    ];

    /**
     * Requisito (cantidad + ciclo en días) para $specialty en $patientPlan. Si no hay fila
     * guardada todavía, devuelve una instancia sin persistir con los defaults (1 cada 30 días).
     */
    public static function requirementFor(string $specialty, string $patientPlan): self
    {
        return static::firstOrNew(
            ['specialty' => $specialty, 'patient_plan' => $patientPlan],
            ['monthly_required_count' => 1, 'cycle_days' => 30]
        );
    }

    /**
     * Requisito combinado (cualquiera especialidad, un solo cupo compartido) para $patientPlan
     * — hoy solo lo usa Mantenimiento Pleno. Si no hay fila guardada, defaults a 1 cada 60 días.
     */
    public static function combinedRequirementFor(string $patientPlan): self
    {
        return static::firstOrNew(
            ['specialty' => 'cualquiera', 'patient_plan' => $patientPlan],
            ['monthly_required_count' => 1, 'cycle_days' => 60]
        );
    }

    public static function requiredCountFor(string $specialty, ?string $patientPlan = null): int
    {
        $query = static::where('specialty', $specialty);
        if ($patientPlan) {
            $query->where('patient_plan', $patientPlan);
        }

        return (int) ($query->value('monthly_required_count') ?? 1);
    }
}
