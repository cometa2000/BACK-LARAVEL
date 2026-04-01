<?php

namespace App\Console\Commands;

use App\Models\tasks\Tareas;
use App\Models\tasks\Timeline;
use App\Mail\TareaVencimientoProximoMail;
use App\Mail\TareaVencidaMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class VerificarTareasVencimiento extends Command
{
    protected $signature = 'tareas:verificar-vencimiento';

    protected $description = 'Verifica las tareas próximas a vencer y las vencidas, y envía notificaciones a los miembros asignados';

    public function handle()
    {
        $this->info('🔍 Iniciando verificación de tareas...');

        // ✅ FIX: Usar startOfDay() para comparar días completos y evitar
        //    problemas cuando la hora de due_date y la hora actual difieren.
        //    Carbon castea due_date como 'date' (sin hora), por lo que
        //    diffInDays con timestamps mixtos puede devolver 0 cuando debería
        //    devolver 1 y la notificación nunca se dispara.
        $now     = Carbon::now();
        $hoy     = Carbon::now()->startOfDay();

        $notificacionesEnviadas = 0;

        try {
            // ====================================================
            // 1️⃣  TAREAS PRÓXIMAS A VENCER
            // ====================================================
            $this->info('📅 Verificando tareas próximas a vencer...');

            $tareasProximas = Tareas::where('notifications_enabled', true)
                ->whereNotNull('due_date')
                ->whereNotNull('notification_days_before')
                ->whereNull('notification_sent_at')           // aún no notificada
                ->where('due_date', '>=', $hoy->toDateString()) // vence hoy o después
                ->where('status', '!=', 'completada')
                ->with(['assignedUsers', 'lista.grupo'])
                ->get();

            $this->info("   Encontradas {$tareasProximas->count()} tareas con notificaciones habilitadas");

            foreach ($tareasProximas as $tarea) {
                // ✅ FIX: comparar inicio de día vs inicio de día
                $dueDate      = Carbon::parse($tarea->due_date)->startOfDay();
                $diasRestantes = (int) $hoy->diffInDays($dueDate, false); // false → con signo

                $this->line("   → Tarea '{$tarea->name}' | due: {$dueDate->toDateString()} | días restantes: {$diasRestantes} | umbral: {$tarea->notification_days_before}");

                // Enviar si faltan ≤ días configurados (y la fecha no pasó)
                if ($diasRestantes >= 0 && $diasRestantes <= $tarea->notification_days_before) {

                    if ($tarea->assignedUsers && $tarea->assignedUsers->count() > 0) {
                        $this->line("   ⏰ Enviando notificación: vence en {$diasRestantes} día(s)");

                        foreach ($tarea->assignedUsers as $usuario) {
                            try {
                                Mail::to($usuario->email)->send(
                                    new TareaVencimientoProximoMail($tarea, $usuario, $diasRestantes)
                                );
                                $this->info("      ✅ Email enviado a {$usuario->email}");
                                $notificacionesEnviadas++;
                            } catch (\Exception $e) {
                                $this->error("      ❌ Error enviando a {$usuario->email}: {$e->getMessage()}");
                                Log::error("Error enviando notificación próxima a vencer", [
                                    'tarea_id' => $tarea->id,
                                    'email'    => $usuario->email,
                                    'error'    => $e->getMessage(),
                                ]);
                            }
                        }

                        // Marcar como notificada
                        $tarea->notification_sent_at = $now;
                        $tarea->save();

                        // Timeline
                        Timeline::create([
                            'tarea_id' => $tarea->id,
                            'user_id'  => $tarea->user_id,
                            'action'   => 'notification_sent',
                            'details'  => [
                                'type'           => 'vencimiento_proximo',
                                'dias_restantes' => $diasRestantes,
                                'notified_users' => $tarea->assignedUsers->count(),
                            ],
                        ]);

                        $this->info("      📝 Notificación registrada en timeline");
                    } else {
                        $this->warn("      ⚠️  No hay miembros asignados a '{$tarea->name}'");
                    }
                }
                // else: días restantes fuera del umbral → no es momento aún
            }

            // ====================================================
            // 2️⃣  TAREAS VENCIDAS
            // ====================================================
            $this->info('');
            $this->info('❌ Verificando tareas vencidas...');

            $tareasVencidas = Tareas::where('notifications_enabled', true)
                ->whereNotNull('due_date')
                ->whereNull('overdue_notification_sent_at')          // aún no notificada
                ->where('due_date', '<', $hoy->toDateString())        // ✅ FIX: comparar fecha, no datetime
                ->where('status', '!=', 'completada')
                ->with(['assignedUsers', 'lista.grupo'])
                ->get();

            $this->info("   Encontradas {$tareasVencidas->count()} tareas vencidas sin notificar");

            foreach ($tareasVencidas as $tarea) {
                $this->line("   ❌ Tarea '{$tarea->name}' está vencida");

                if ($tarea->assignedUsers && $tarea->assignedUsers->count() > 0) {
                    foreach ($tarea->assignedUsers as $usuario) {
                        try {
                            Mail::to($usuario->email)->send(
                                new TareaVencidaMail($tarea, $usuario)
                            );
                            $this->info("      ✅ Email de vencimiento enviado a {$usuario->email}");
                            $notificacionesEnviadas++;
                        } catch (\Exception $e) {
                            $this->error("      ❌ Error enviando a {$usuario->email}: {$e->getMessage()}");
                            Log::error("Error enviando notificación de tarea vencida", [
                                'tarea_id' => $tarea->id,
                                'email'    => $usuario->email,
                                'error'    => $e->getMessage(),
                            ]);
                        }
                    }

                    $tarea->overdue_notification_sent_at = $now;
                    $tarea->save();

                    Timeline::create([
                        'tarea_id' => $tarea->id,
                        'user_id'  => $tarea->user_id,
                        'action'   => 'notification_sent',
                        'details'  => [
                            'type'           => 'tarea_vencida',
                            'notified_users' => $tarea->assignedUsers->count(),
                        ],
                    ]);

                    $this->info("      📝 Notificación de vencimiento registrada en timeline");
                } else {
                    $this->warn("      ⚠️  No hay miembros asignados a '{$tarea->name}'");
                }
            }

            // ====================================================
            // 📊  RESUMEN
            // ====================================================
            $this->info('');
            $this->info('═══════════════════════════════════════');
            $this->info("✅ Verificación completada");
            $this->info("📧 Total de notificaciones enviadas: {$notificacionesEnviadas}");
            $this->info('═══════════════════════════════════════');

            Log::info("Verificación de tareas completada: {$notificacionesEnviadas} notificaciones enviadas");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error durante la verificación de tareas');
            $this->error($e->getMessage());

            Log::error('Error en VerificarTareasVencimiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}