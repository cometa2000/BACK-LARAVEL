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
        // ✅ Solo crear si NO existe
        if (!Schema::hasTable('actividades')) {
            Schema::create('actividades', function (Blueprint $table) {
                $table->id();
                $table->string('type', 50);
                $table->string('description');
                $table->json('changes')->nullable();
                $table->unsignedBigInteger('tarea_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('tarea_id')
                      ->references('id')
                      ->on('tareas')
                      ->onDelete('cascade');
                
                $table->foreign('user_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};
