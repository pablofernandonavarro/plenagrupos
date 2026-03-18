<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionAttendance extends Model
{
    protected $fillable = ['session_id', 'user_id', 'checked_in_at'];

    protected $casts = ['checked_in_at' => 'datetime'];

    public function session()
    {
        return $this->belongsTo(TherapeuticSession::class, 'session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
