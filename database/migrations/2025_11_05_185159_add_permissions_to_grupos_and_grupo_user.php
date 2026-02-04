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
        // Agregar configuración de permisos generales a la tabla grupos
        Schema::table('grupos', function (Blueprint $table) {
            // Tipo de permiso general del grupo
            // 'all' = Todos con permisos completos (default)
            // 'readonly' = Solo lectura para todos
            // 'custom' = Permisos personalizados por usuario
            $table->enum('permission_type', ['all', 'readonly', 'custom'])
                  ->default('all')
                  ->after('is_starred');
        });

        // Agregar permisos específicos a la tabla pivote grupo_user
        Schema::table('grupo_user', function (Blueprint $table) {
            // Nivel de permiso para cada usuario
            // 'read' = Solo lectura
            // 'write' = Lectura y escritura (crear, editar, eliminar)
            $table->enum('permission_level', ['read', 'write'])
                  ->default('write')
                  ->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grupos', function (Blueprint $table) {
            $table->dropColumn('permission_type');
        });

        Schema::table('grupo_user', function (Blueprint $table) {
            $table->dropColumn('permission_level');
        });
    }
};