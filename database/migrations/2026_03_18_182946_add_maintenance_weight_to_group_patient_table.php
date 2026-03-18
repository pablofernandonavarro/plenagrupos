<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_patient', function (Blueprint $table) {
            $table->decimal('maintenance_weight', 5, 2)->nullable()->after('joined_at');
        });
    }

    public function down(): void
    {
        Schema::table('group_patient', function (Blueprint $table) {
            $table->dropColumn('maintenance_weight');
        });
    }
};
