<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_rules', function (Blueprint $table) {
            $table->renameColumn('weekly_limit', 'monthly_limit');
        });
    }

    public function down(): void
    {
        Schema::table('plan_rules', function (Blueprint $table) {
            $table->renameColumn('monthly_limit', 'weekly_limit');
        });
    }
};
