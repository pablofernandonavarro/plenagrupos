<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupAttendance extends Model
{
    protected $fillable = ['group_id', 'user_id', 'attended_at'];

    protected $casts = ['attended_at' => 'datetime'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function weightRecord()
    {
        return $this->hasOne(WeightRecord::class, 'attendance_id');
    }
}
