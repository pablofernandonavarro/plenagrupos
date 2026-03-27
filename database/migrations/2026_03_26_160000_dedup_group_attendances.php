<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For each (user_id, group_id, date) group, keep the row with the lowest id
        // and delete the rest. Also preserve the latest left_at value if any.

        $duplicates = DB::select("
            SELECT user_id, group_id, DATE(attended_at) AS day
            FROM group_attendances
            GROUP BY user_id, group_id, DATE(attended_at)
            HAVING COUNT(*) > 1
        ");

        foreach ($duplicates as $dup) {
            $rows = DB::table('group_attendances')
                ->where('user_id', $dup->user_id)
                ->where('group_id', $dup->group_id)
                ->whereDate('attended_at', $dup->day)
                ->orderBy('attended_at')
                ->get();

            $keepId = $rows->first()->id;

            // If any row has a left_at, use the latest one on the keeper
            $latestLeftAt = $rows->whereNotNull('left_at')->sortByDesc('left_at')->first()?->left_at;

            if ($latestLeftAt) {
                DB::table('group_attendances')
                    ->where('id', $keepId)
                    ->update(['left_at' => $latestLeftAt]);
            }

            // Delete all duplicates (rows that are not the keeper)
            DB::table('group_attendances')
                ->where('user_id', $dup->user_id)
                ->where('group_id', $dup->group_id)
                ->whereDate('attended_at', $dup->day)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        // Add unique index to prevent future duplicates
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_attendance_per_day ON group_attendances (user_id, group_id, DATE(attended_at))');
        } else {
            // MySQL: use generated column for functional unique index support
            DB::statement('ALTER TABLE group_attendances ADD COLUMN attended_date DATE GENERATED ALWAYS AS (DATE(attended_at)) STORED');
            DB::statement('ALTER TABLE group_attendances ADD UNIQUE KEY uq_attendance_per_day (user_id, group_id, attended_date)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS uq_attendance_per_day');
        } else {
            DB::statement('ALTER TABLE group_attendances DROP KEY uq_attendance_per_day');
            DB::statement('ALTER TABLE group_attendances DROP COLUMN attended_date');
        }
    }
};
