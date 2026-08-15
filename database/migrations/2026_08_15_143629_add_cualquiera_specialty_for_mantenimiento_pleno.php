<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE appointment_requirements MODIFY specialty ENUM('medico','nutricionista','cualquiera') NOT NULL");

        DB::table('appointment_requirements')->where('patient_plan', 'mantenimiento_pleno')->delete();

        DB::table('appointment_requirements')->insert([
            'patient_plan' => 'mantenimiento_pleno',
            'specialty' => 'cualquiera',
            'monthly_required_count' => 1,
            'cycle_days' => 60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('appointment_requirements')->where('patient_plan', 'mantenimiento_pleno')->delete();

        $now = now();
        DB::table('appointment_requirements')->insert([
            ['patient_plan' => 'mantenimiento_pleno', 'specialty' => 'medico', 'monthly_required_count' => 1, 'cycle_days' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['patient_plan' => 'mantenimiento_pleno', 'specialty' => 'nutricionista', 'monthly_required_count' => 1, 'cycle_days' => 30, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::statement("ALTER TABLE appointment_requirements MODIFY specialty ENUM('medico','nutricionista') NOT NULL");
    }
};
