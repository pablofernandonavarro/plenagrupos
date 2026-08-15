<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant']);
            $table->text('content');
            // Auditoría: qué contexto (estructurado + fragmentos semánticos) se usó
            // para generar la respuesta. Null en mensajes de rol 'user'.
            $table->json('retrieved_context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['conversation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};
