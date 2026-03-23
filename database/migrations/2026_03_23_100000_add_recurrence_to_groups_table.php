<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('recurrence_type', 20)->default('none')->after('auto_sessions');
            $table->unsignedSmallInteger('recurrence_interval')->default(1)->after('recurrence_type');
            $table->date('recurrence_end_date')->nullable()->after('recurrence_interval');
        });

        // Migrate existing data
        DB::table('groups')
            ->where('auto_sessions', true)
            ->whereNotNull('meeting_day')
            ->update(['recurrence_type' => 'weekly', 'recurrence_interval' => 1]);
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['recurrence_type', 'recurrence_interval', 'recurrence_end_date']);
        });
    }
};
