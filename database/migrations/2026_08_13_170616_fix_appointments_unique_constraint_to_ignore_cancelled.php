<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El unique(professional_id, starts_at) original bloquea el slot para siempre incluso
     * después de cancelar el turno, porque MySQL no distingue por `status`. Se reemplaza por
     * una columna virtual que es NULL cuando el turno está cancelado — MySQL permite múltiples
     * NULL en un índice único, así que un slot cancelado vuelve a estar disponible.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // La FK de professional_id necesita algún índice que la cubra antes de poder
            // soltar el unique(professional_id, starts_at) que hoy es el único que la cubre.
            $table->index('professional_id', 'appointments_professional_id_index');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique('appointments_professional_id_starts_at_unique');
        });

        DB::statement("
            ALTER TABLE appointments
            ADD COLUMN active_slot_key VARCHAR(120)
            GENERATED ALWAYS AS (
                CASE WHEN status = 'cancelled' THEN NULL ELSE CONCAT(professional_id, ':', starts_at) END
            ) VIRTUAL
        ");

        Schema::table('appointments', function (Blueprint $table) {
            $table->unique('active_slot_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique(['active_slot_key']);
            $table->dropColumn('active_slot_key');
            $table->unique(['professional_id', 'starts_at']);
            $table->dropIndex('appointments_professional_id_index');
        });
    }
};
