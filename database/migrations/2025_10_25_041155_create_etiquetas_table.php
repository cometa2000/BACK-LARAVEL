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
        Schema::create('etiquetas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('color', 20); // hex color like #FF6B6B
            $table->unsignedBigInteger('tarea_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tarea_id')
                  ->references('id')
                  ->on('tareas')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etiquetas');
    }
};
