<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     Schema::table('listas', function (Blueprint $table) {
    //         $table->unsignedBigInteger('grupo_id')->nullable()->after('orden');
    //         $table->foreign('grupo_id')->references('id')->on('grupos')->onDelete('cascade');
    //     });
    // }

    
    // public function down(): void
    // {
    //     Schema::table('listas', function (Blueprint $table) {
    //         $table->dropForeign(['grupo_id']);
    //         $table->dropColumn('grupo_id');
    //     });
    // }


    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('listas', function (Blueprint $table) {
            // Verificar si la columna NO existe antes de agregarla
            if (!Schema::hasColumn('listas', 'grupo_id')) {
                $table->unsignedBigInteger('grupo_id')->nullable()->after('orden');
                $table->foreign('grupo_id')->references('id')->on('grupos')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listas', function (Blueprint $table) {
            // Verificar si la columna existe antes de eliminarla
            if (Schema::hasColumn('listas', 'grupo_id')) {
                $table->dropForeign(['grupo_id']);
                $table->dropColumn('grupo_id');
            }
        });
    }
};
