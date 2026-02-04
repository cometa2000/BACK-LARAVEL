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
        Schema::table('documentos', function (Blueprint $table) {
            // Asegurar que estos campos existen y tienen los índices correctos
            if (!Schema::hasColumn('documentos', 'type')) {
                $table->enum('type', ['file', 'folder'])->default('file')->after('name');
            }
            
            if (!Schema::hasColumn('documentos', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('type');
            }

            // Agregar índices para mejorar el rendimiento de las consultas
            if (!Schema::hasIndex('documentos', ['parent_id'])) {
                $table->index('parent_id');
            }
            
            if (!Schema::hasIndex('documentos', ['sucursale_id'])) {
                $table->index('sucursale_id');
            }
            
            if (!Schema::hasIndex('documentos', ['type'])) {
                $table->index('type');
            }

            // Agregar campo para orden personalizado dentro de carpetas (opcional)
            if (!Schema::hasColumn('documentos', 'order')) {
                $table->integer('order')->default(0)->after('parent_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->dropColumn(['order']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['sucursale_id']);
            $table->dropIndex(['type']);
        });
    }
};