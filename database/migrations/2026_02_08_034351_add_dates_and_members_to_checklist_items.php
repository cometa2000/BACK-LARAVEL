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
        // Agregar campos de fecha a checklist_items
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('orden');
        });

        // Crear tabla pivot para la relación many-to-many entre checklist_items y users
        Schema::create('checklist_item_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('checklist_item_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['checklist_item_id', 'user_id']);
            
            $table->foreign('checklist_item_id')
                  ->references('id')
                  ->on('checklist_items')
                  ->onDelete('cascade');
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_item_user');
        
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }
};