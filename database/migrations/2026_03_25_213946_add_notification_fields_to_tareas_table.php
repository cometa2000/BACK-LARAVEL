<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega los campos necesarios para el sistema de notificaciones de vencimiento de tareas.
     */
    public function up(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            // Solo agregar si no existen (seguro para correr en producción)
            if (!Schema::hasColumn('tareas', 'notifications_enabled')) {
                $table->boolean('notifications_enabled')
                      ->default(false)
                      ->after('status')
                      ->comment('Indica si la tarea tiene notificaciones de vencimiento activas');
            }

            if (!Schema::hasColumn('tareas', 'notification_days_before')) {
                $table->unsignedTinyInteger('notification_days_before')
                      ->nullable()
                      ->after('notifications_enabled')
                      ->comment('Días antes del vencimiento para enviar la notificación previa');
            }

            if (!Schema::hasColumn('tareas', 'notification_sent_at')) {
                $table->timestamp('notification_sent_at')
                      ->nullable()
                      ->after('notification_days_before')
                      ->comment('Fecha en que se envió la notificación de vencimiento próximo');
            }

            if (!Schema::hasColumn('tareas', 'overdue_notification_sent_at')) {
                $table->timestamp('overdue_notification_sent_at')
                      ->nullable()
                      ->after('notification_sent_at')
                      ->comment('Fecha en que se envió la notificación de tarea vencida');
            }
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
                'overdue_notification_sent_at',
            ]);
        });
    }
};