<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiMessage extends Model
{
    public $timestamps = false;

    protected $fillable = ['conversation_id', 'role', 'content', 'retrieved_context'];

    protected $casts = [
        'retrieved_context' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AiMessage $message) {
            $message->created_at ??= now();
        });
    }

    public function conversation()
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}
