<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentRequirement extends Model
{
    protected $fillable = ['patient_plan', 'specialty', 'monthly_required_count'];

    protected $casts = [
        'monthly_required_count' => 'integer',
    ];

    public static function requiredCountFor(string $specialty, ?string $patientPlan = null): int
    {
        $query = static::where('specialty', $specialty);
        if ($patientPlan) {
            $query->where('patient_plan', $patientPlan);
        }

        return (int) ($query->value('monthly_required_count') ?? 1);
    }
}
