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
        Schema::table('weight_records', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['attendance_id']);
            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
            $table->foreign('attendance_id')->references('id')->on('group_attendances')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weight_records', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['attendance_id']);
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
            $table->foreign('attendance_id')->references('id')->on('group_attendances')->cascadeOnDelete();
        });
    }
};
