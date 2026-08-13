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
        Schema::table('groups', function (Blueprint $table) {
            // 1-4 = 1er..4to; 5 = último. NULL = recurrencia mensual por número de día fijo (comportamiento previo).
            $table->unsignedTinyInteger('monthly_week_ordinal')->nullable()->after('recurrence_interval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('monthly_week_ordinal');
        });
    }
};
