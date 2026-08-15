<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patient_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            // 'weight_note' | 'inbody_note' | 'coordinator_note' | 'appointment_note' |
            // 'personal_goal' | 'patient_status_note'
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->text('content');
            $table->json('embedding');
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
            $table->index(['patient_id', 'source_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_embeddings');
    }
};
