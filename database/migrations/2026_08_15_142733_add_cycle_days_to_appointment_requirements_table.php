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
        Schema::table('appointment_requirements', function (Blueprint $table) {
            $table->unsignedSmallInteger('cycle_days')->default(30)->after('monthly_required_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_requirements', function (Blueprint $table) {
            $table->dropColumn('cycle_days');
        });
    }
};
