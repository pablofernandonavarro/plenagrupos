<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_membership_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->string('join_source', 20)->default('manual');
            $table->index(['user_id', 'group_id']);
        });

        // Migrate existing memberships: create a log entry for every current group_patient row
        \Illuminate\Support\Facades\DB::statement('
            INSERT INTO group_membership_logs (group_id, user_id, joined_at, left_at, join_source)
            SELECT group_id, user_id, joined_at, left_at, COALESCE(join_source, \'manual\')
            FROM group_patient
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('group_membership_logs');
    }
};
