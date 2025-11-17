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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Usuario que realizó la acción
            $table->unsignedBigInteger('tarea_id'); // Tarea relacionada
            $table->string('type'); // tipo: 'comment', 'status_change', 'assignment', 'attachment', 'due_date', 'checklist'
            $table->text('description'); // Descripción de la actividad
            $table->json('metadata')->nullable(); // Datos adicionales (anterior_estado, nuevo_estado, etc.)
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('tarea_id')->references('id')->on('tareas')->onDelete('cascade');
            
            // Índices para mejorar el rendimiento
            $table->index('tarea_id');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
