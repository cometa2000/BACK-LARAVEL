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
        Schema::create('tarea_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tarea_id');
            $table->enum('tipo', ['archivo', 'enlace'])->default('archivo');
            $table->string('nombre');
            $table->string('url')->nullable(); // Para enlaces
            $table->string('file_path')->nullable(); // Para archivos
            $table->string('mime_type')->nullable();
            $table->bigInteger('size')->nullable(); // Tamaño en bytes
            $table->text('preview')->nullable(); // Base64 preview para imágenes
            $table->timestamps();

            $table->foreign('tarea_id')->references('id')->on('tareas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarea_adjuntos');
    }
};
