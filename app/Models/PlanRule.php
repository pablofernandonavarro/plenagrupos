<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanRule extends Model
{
    protected $fillable = ['patient_plan', 'group_type', 'monthly_limit', 'weekend_unlimited'];

    protected $casts = [
        'weekend_unlimited' => 'boolean',
        'monthly_limit'     => 'integer',
    ];

    public static function find(string $patientPlan, string $groupType): ?self
    {
        return static::where('patient_plan', $patientPlan)
            ->where('group_type', $groupType)
            ->first();
    }
}
