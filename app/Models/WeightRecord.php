<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeightRecord extends Model
{
    protected $fillable = ['user_id', 'group_id', 'attendance_id', 'weight', 'notes', 'recorded_at'];

    protected $casts = [
        'weight' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function attendance()
    {
        return $this->belongsTo(GroupAttendance::class, 'attendance_id');
    }
}
