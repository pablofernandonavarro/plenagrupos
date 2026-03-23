<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->json('meeting_days')->nullable()->after('meeting_day');
        });

        // Migrate existing single meeting_day → meeting_days array
        DB::table('groups')
            ->whereNotNull('meeting_day')
            ->get()
            ->each(fn($g) => DB::table('groups')
                ->where('id', $g->id)
                ->update(['meeting_days' => json_encode([$g->meeting_day])])
            );
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('meeting_days');
        });
    }
};
