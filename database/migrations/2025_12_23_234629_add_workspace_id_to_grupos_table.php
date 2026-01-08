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
        // Verificar si la columna ya existe antes de agregarla
        if (!Schema::hasColumn('grupos', 'workspace_id')) {
            Schema::table('grupos', function (Blueprint $table) {
                $table->unsignedBigInteger('workspace_id')->nullable()->after('user_id');
                $table->index('workspace_id');
                $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('grupos', 'workspace_id')) {
            Schema::table('grupos', function (Blueprint $table) {
                $table->dropForeign(['workspace_id']);
                $table->dropIndex(['workspace_id']);
                $table->dropColumn('workspace_id');
            });
        }
    }
};