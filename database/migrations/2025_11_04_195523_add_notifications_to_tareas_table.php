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
        Schema::table('tareas', function (Blueprint $table) {
            // Habilitar notificaciones
            $table->boolean('notifications_enabled')->default(false)->after('due_date');
            
            // Días antes de vencimiento para notificar (1-5)
            $table->integer('notification_days_before')->nullable()->after('notifications_enabled');
            
            // Registro de notificaciones enviadas
            $table->timestamp('notification_sent_at')->nullable()->after('notification_days_before');
            $table->timestamp('overdue_notification_sent_at')->nullable()->after('notification_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            $table->dropColumn([
                'notifications_enabled',
                'notification_days_before',
                'notification_sent_at',
                'overdue_notification_sent_at'
            ]);
        });
    }
};
