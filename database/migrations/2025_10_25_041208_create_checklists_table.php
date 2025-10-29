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
        Schema::create('checklists', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedBigInteger('tarea_id');
            $table->integer('orden')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tarea_id')
                  ->references('id')
                  ->on('tareas')
                  ->onDelete('cascade');
        });

        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->boolean('completed')->default(false);
            $table->unsignedBigInteger('checklist_id');
            $table->integer('orden')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('checklist_id')
                  ->references('id')
                  ->on('checklists')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('checklists');
    }
};
