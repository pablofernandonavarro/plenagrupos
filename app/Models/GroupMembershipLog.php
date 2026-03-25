<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMembershipLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['group_id', 'user_id', 'joined_at', 'left_at', 'join_source'];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at'   => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
