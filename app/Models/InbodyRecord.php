<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InbodyRecord extends Model
{
    protected $fillable = [
        'user_id', 'test_date', 'weight', 'skeletal_muscle_mass',
        'body_fat_mass', 'body_fat_percentage', 'bmi', 'basal_metabolic_rate',
        'visceral_fat_level', 'total_body_water', 'proteins', 'minerals',
        'inbody_score', 'obesity_degree', 'image_path', 'notes',
    ];

    protected $casts = [
        'test_date'           => 'date',
        'weight'              => 'float',
        'skeletal_muscle_mass'=> 'float',
        'body_fat_mass'       => 'float',
        'body_fat_percentage' => 'float',
        'bmi'                 => 'float',
        'basal_metabolic_rate'=> 'integer',
        'visceral_fat_level'  => 'float',
        'total_body_water'    => 'float',
        'proteins'            => 'float',
        'minerals'            => 'float',
        'inbody_score'        => 'integer',
        'obesity_degree'      => 'float',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
