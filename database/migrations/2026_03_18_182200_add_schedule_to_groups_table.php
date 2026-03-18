<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('meeting_day')->nullable()->after('description');   // ej: "Lunes", "Martes y Jueves"
            $table->time('meeting_time')->nullable()->after('meeting_day');    // ej: 09:00
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['meeting_day', 'meeting_time']);
        });
    }
};
