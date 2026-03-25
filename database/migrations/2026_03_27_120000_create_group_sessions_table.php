<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->date('session_date');
            $table->unsignedInteger('sequence_number');
            $table->timestamps();

            $table->unique(['group_id', 'session_date']);
            $table->unique(['group_id', 'sequence_number']);
            $table->index(['group_id', 'session_date']);
        });

        Schema::table('group_attendances', function (Blueprint $table) {
            $table->foreignId('group_session_id')->nullable()->after('group_id')->constrained('group_sessions')->nullOnDelete();
        });

        $tz = 'America/Argentina/Buenos_Aires';

        $byGroup = DB::table('group_attendances')
            ->whereNotNull('attended_at')
            ->orderBy('group_id')
            ->orderBy('attended_at')
            ->get(['id', 'group_id', 'attended_at'])
            ->groupBy('group_id');

        foreach ($byGroup as $groupId => $rows) {
            $days = $rows->map(function ($r) use ($tz) {
                return Carbon::parse($r->attended_at)->timezone($tz)->toDateString();
            })->unique()->sort()->values();

            foreach ($days as $sessionDate) {
                $seq = (int) DB::table('group_sessions')->where('group_id', $groupId)->max('sequence_number') + 1;
                $sessionId = DB::table('group_sessions')->insertGetId([
                    'group_id' => $groupId,
                    'session_date' => $sessionDate,
                    'sequence_number' => $seq,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('group_attendances')
                    ->where('group_id', $groupId)
                    ->whereDate('attended_at', $sessionDate)
                    ->update(['group_session_id' => $sessionId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('group_attendances', function (Blueprint $table) {
            $table->dropForeign(['group_session_id']);
            $table->dropColumn('group_session_id');
        });

        Schema::dropIfExists('group_sessions');
    }
};
