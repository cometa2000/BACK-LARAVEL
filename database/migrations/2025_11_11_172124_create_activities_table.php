<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Usuario que recibe la notificación
            $table->unsignedBigInteger('from_user_id')->nullable(); // Usuario que generó la acción
            $table->unsignedBigInteger('tarea_id')->nullable(); // Tarea relacionada (puede ser null para notificaciones generales)
            $table->unsignedBigInteger('grupo_id')->nullable(); // Grupo relacionado
            $table->string('type'); // tipo: 'task_assigned', 'task_completed', 'comment', 'mention', 'due_date_reminder', 'permission_changed'
            $table->string('title'); // Título de la notificación
            $table->text('message'); // Mensaje de la notificación
            $table->json('data')->nullable(); // Datos adicionales (URLs, IDs, etc.)
            $table->boolean('is_read')->default(false); // Si fue leída
            $table->timestamp('read_at')->nullable(); // Cuándo fue leída
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('tarea_id')->references('id')->on('tareas')->onDelete('cascade');
            $table->foreign('grupo_id')->references('id')->on('grupos')->onDelete('cascade');
            
            // Índices
            $table->index(['user_id', 'is_read']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
