<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TherapeuticSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'name',
        'session_date',
        'qr_token',
        'status',
        'created_by',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($session) {
            if (empty($session->qr_token)) {
                $session->qr_token = Str::uuid();
            }
        });
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances()
    {
        return $this->hasMany(SessionAttendance::class, 'session_id');
    }

    public function weightRecords()
    {
        return $this->hasMany(WeightRecord::class, 'session_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
