<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('body');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('whatsapp_templates')->insert([
            [
                'key' => 'appointment_booked',
                'body' => "Hola {paciente}! 👋 Se reservó tu turno de *{especialidad}* con {profesional} el {fecha} a las {hora}.\n\nConfirmar: {link_confirmar}\nCancelar: {link_cancelar}",
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'appointment_reminder',
                'body' => "Hola {paciente}! Te recordamos tu turno de *{especialidad}* con {profesional} mañana {fecha} a las {hora}.\n\nConfirmar: {link_confirmar}\nCancelar: {link_cancelar}",
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'appointment_cancelled',
                'body' => "Hola {paciente}, tu turno de *{especialidad}* con {profesional} el {fecha} a las {hora} fue cancelado.",
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
