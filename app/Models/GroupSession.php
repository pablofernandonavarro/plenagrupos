<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupSession extends Model
{
    protected $fillable = [
        'group_id',
        'session_date',
        'sequence_number',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(GroupAttendance::class, 'group_session_id');
    }
}
