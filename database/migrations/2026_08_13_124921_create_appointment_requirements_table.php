<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointment_requirements', function (Blueprint $table) {
            $table->id();
            $table->enum('specialty', ['medico', 'nutricionista'])->unique();
            $table->unsignedTinyInteger('monthly_required_count')->default(1);
            $table->timestamps();
        });

        DB::table('appointment_requirements')->insert([
            ['specialty' => 'medico', 'monthly_required_count' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['specialty' => 'nutricionista', 'monthly_required_count' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_requirements');
    }
};
