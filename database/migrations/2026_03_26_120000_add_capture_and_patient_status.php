<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('patient_status', 20)->nullable();
            $table->timestamp('patient_status_at')->nullable();
            $table->text('patient_status_note')->nullable();
        });

        Schema::table('group_patient', function (Blueprint $table) {
            $table->string('join_source', 20)->default('manual');
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->text('first_device_user_agent')->nullable();
        });

        DB::table('users')->where('role', 'patient')->update(['patient_status' => 'active']);

        DB::table('group_patient')->update(['join_source' => 'manual']);
    }

    public function down(): void
    {
        Schema::table('group_patient', function (Blueprint $table) {
            $table->dropColumn([
                'join_source',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_content',
                'first_device_user_agent',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['patient_status', 'patient_status_at', 'patient_status_note']);
        });
    }
};
