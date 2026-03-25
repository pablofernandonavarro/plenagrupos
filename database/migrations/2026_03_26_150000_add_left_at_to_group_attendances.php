<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_attendances', function (Blueprint $table) {
            $table->timestamp('left_at')->nullable()->after('attended_at');
        });
    }

    public function down(): void
    {
        Schema::table('group_attendances', function (Blueprint $table) {
            $table->dropColumn('left_at');
        });
    }
};
