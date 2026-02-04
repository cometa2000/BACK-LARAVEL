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
        Schema::create('listas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        if (Schema::hasTable('tareas')) {
            Schema::table('tareas', function (Blueprint $table) {
                $table->unsignedBigInteger('lista_id')->nullable()->after('grupo_id');
                $table->foreign('lista_id')->references('id')->on('listas')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listas');
    }


};
