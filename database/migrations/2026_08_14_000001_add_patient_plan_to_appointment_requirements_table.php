<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop unique on specialty, add patient_plan, new unique(patient_plan, specialty)
        Schema::table('appointment_requirements', function (Blueprint $table) {
            $table->dropUnique(['specialty']);
            $table->string('patient_plan')->after('id')->default('descenso');
        });

        // Seed one row per plan × specialty (preserve existing counts for each specialty)
        $plans = ['descenso', 'mantenimiento', 'mantenimiento_pleno'];
        $specialties = ['medico', 'nutricionista'];
        $existing = DB::table('appointment_requirements')->get()->keyBy('specialty');

        // Remove old flat rows and recreate per-plan
        DB::table('appointment_requirements')->truncate();
        $now = now();
        foreach ($plans as $plan) {
            foreach ($specialties as $specialty) {
                $count = $existing->get($specialty)?->monthly_required_count ?? 1;
                DB::table('appointment_requirements')->insert([
                    'patient_plan'          => $plan,
                    'specialty'             => $specialty,
                    'monthly_required_count' => $count,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ]);
            }
        }

        Schema::table('appointment_requirements', function (Blueprint $table) {
            $table->unique(['patient_plan', 'specialty']);
        });
    }

    public function down(): void
    {
        Schema::table('appointment_requirements', function (Blueprint $table) {
            $table->dropUnique(['patient_plan', 'specialty']);
            $table->dropColumn('patient_plan');
        });

        DB::table('appointment_requirements')->truncate();
        $now = now();
        DB::table('appointment_requirements')->insert([
            ['specialty' => 'medico', 'monthly_required_count' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['specialty' => 'nutricionista', 'monthly_required_count' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('appointment_requirements', function (Blueprint $table) {
            $table->unique('specialty');
        });
    }
};
