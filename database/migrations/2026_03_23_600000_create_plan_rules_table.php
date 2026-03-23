<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_rules', function (Blueprint $table) {
            $table->id();
            $table->string('patient_plan');   // plan del paciente
            $table->string('group_type');     // tipo de grupo al que intenta ingresar
            $table->unsignedTinyInteger('weekly_limit')->nullable(); // null = sin límite
            $table->boolean('weekend_unlimited')->default(false);    // finde sin restricción
            $table->timestamps();
            $table->unique(['patient_plan', 'group_type']);
        });

        // Reglas de ejemplo (editables desde el admin)
        $plans      = ['descenso', 'mantenimiento', 'mantenimiento_pleno'];
        $groupTypes = ['descenso', 'mantenimiento', 'mantenimiento_pleno'];
        $now = now();

        foreach ($plans as $plan) {
            foreach ($groupTypes as $gt) {
                DB::table('plan_rules')->insert([
                    'patient_plan'      => $plan,
                    'group_type'        => $gt,
                    'weekly_limit'      => null, // sin límite por defecto
                    'weekend_unlimited' => false,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_rules');
    }
};
