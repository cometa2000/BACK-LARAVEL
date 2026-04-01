<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_areas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->comment('Nombre del área, p.ej. "Subdirección Académica"');
            $table->string('descripcion', 500)->nullable();
            $table->foreignId('responsable_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('Usuario de la sede principal responsable de esta área');
            $table->boolean('activo')->default(true)->comment('Si aparece en el selector de destinatarios');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['activo', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_areas');
    }
};