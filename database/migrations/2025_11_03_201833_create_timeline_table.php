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
        Schema::create('timeline', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tarea_id');
            $table->unsignedBigInteger('user_id');
            $table->string('action'); // 'assigned_members', 'unassigned_member', 'updated', 'created', etc.
            $table->json('details')->nullable(); // Detalles adicionales de la acción
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('tarea_id')
                ->references('id')
                ->on('tareas')
                ->onDelete('cascade');
                
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            
            // Índices para mejorar rendimiento
            $table->index('tarea_id');
            $table->index('user_id');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timeline');
    }
};
