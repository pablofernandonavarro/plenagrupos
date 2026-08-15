<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointment_requirements', function (Blueprint $table) {
            $table->enum('specialty', ['medico', 'nutricionista', 'cualquiera'])->change();
        });

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

        Schema::table('appointment_requirements', function (Blueprint $table) {
            $table->enum('specialty', ['medico', 'nutricionista'])->change();
        });
    }
};
