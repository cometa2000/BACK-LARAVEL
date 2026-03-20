<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla pivote que vincula un ticket (o un mensaje de ticket)
     * con una tarea existente del sistema de tareas.
     *
     * ticket_message_id NULL  → tarea adjunta al ticket principal (desde create-ticket)
     * ticket_message_id SET   → tarea adjunta en la conversación  (desde list-ticket / hilo)
     */
    public function up(): void
    {
        Schema::create('ticket_tareas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('tarea_id');

            // NULL → adjunto del ticket principal; SET → adjunto de un mensaje del hilo
            $table->unsignedBigInteger('ticket_message_id')->nullable()
                  ->comment('NULL = adjunto del ticket principal; valor = adjunto en mensaje del hilo');

            $table->unsignedBigInteger('user_id')
                  ->comment('Usuario que adjuntó la tarea');

            $table->timestamps();

            // ── FK ──
            $table->foreign('ticket_id')
                  ->references('id')->on('tickets')
                  ->onDelete('cascade');

            $table->foreign('tarea_id')
                  ->references('id')->on('tareas')
                  ->onDelete('cascade');

            $table->foreign('ticket_message_id')
                  ->references('id')->on('ticket_messages')
                  ->onDelete('set null');

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            // Un ticket no puede tener la misma tarea adjunta dos veces
            // en el mismo contexto (principal o mismo mensaje)
            $table->unique(['ticket_id', 'tarea_id', 'ticket_message_id'], 'unique_ticket_tarea_contexto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_tareas');
    }
};