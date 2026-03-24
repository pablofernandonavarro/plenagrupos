<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbody_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('test_date');
            $table->decimal('weight', 5, 1)->nullable();
            $table->decimal('skeletal_muscle_mass', 5, 1)->nullable();
            $table->decimal('body_fat_mass', 5, 1)->nullable();
            $table->decimal('body_fat_percentage', 4, 1)->nullable();
            $table->decimal('bmi', 4, 1)->nullable();
            $table->integer('basal_metabolic_rate')->nullable();   // kcal
            $table->decimal('visceral_fat_level', 4, 1)->nullable();
            $table->decimal('total_body_water', 5, 1)->nullable();
            $table->decimal('proteins', 5, 1)->nullable();
            $table->decimal('minerals', 5, 1)->nullable();
            $table->integer('inbody_score')->nullable();           // 0–100
            $table->decimal('obesity_degree', 5, 1)->nullable();   // %
            $table->string('image_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbody_records');
    }
};
