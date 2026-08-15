<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientEmbedding extends Model
{
    protected $fillable = ['patient_id', 'source_type', 'source_id', 'content', 'embedding'];

    protected $casts = [
        'embedding' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
