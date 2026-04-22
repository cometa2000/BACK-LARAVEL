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

    protected $description = 'Verifica las tareas próximas a vencer y las vencidas, y envía notificaciones al propietario y miembros asignados';

    public function handle()
    {
        $this->info('🔍 Iniciando verificación de tareas...');

        $now = Carbon::now();
        $hoy = Carbon::now()->startOfDay();

        $notificacionesEnviadas = 0;

        try {
            // ====================================================
            // 1️⃣  TAREAS PRÓXIMAS A VENCER
            // ====================================================
            $this->info('📅 Verificando tareas próximas a vencer...');

            $tareasProximas = Tareas::where('notifications_enabled', true)
                ->whereNotNull('due_date')
                ->whereNotNull('notification_days_before')
                ->whereNull('notification_sent_at')
                ->where('due_date', '>=', $hoy->toDateString())
                ->where('status', '!=', 'completada')
                ->with(['assignedUsers', 'lista.grupo', 'grupo'])
                ->get();

            $this->info("   Encontradas {$tareasProximas->count()} tareas con notificaciones habilitadas");

            foreach ($tareasProximas as $tarea) {
                $dueDate       = Carbon::parse($tarea->due_date)->startOfDay();
                $diasRestantes = (int) $hoy->diffInDays($dueDate, false);

                $this->line("   → Tarea '{$tarea->name}' | due: {$dueDate->toDateString()} | días restantes: {$diasRestantes} | umbral: {$tarea->notification_days_before}");

                if ($diasRestantes >= 0 && $diasRestantes <= $tarea->notification_days_before) {

                    // ─── Construir lista de destinatarios ───────────────────
                    // Usar Collection con keyBy('id') para evitar duplicados
                    $destinatarios = $tarea->assignedUsers->keyBy('id');

                    // 1. Creador de la tarea (user_id en tareas)
                    if ($tarea->user_id) {
                        $creadorTarea = \App\Models\User::find($tarea->user_id);
                        if ($creadorTarea && !$destinatarios->has($creadorTarea->id)) {
                            $destinatarios->put($creadorTarea->id, $creadorTarea);
                        }
                    }

                    // 2. Propietario del grupo al que pertenece la tarea
                    $propietarioGrupoId = $tarea->grupo?->user_id;
                    if ($propietarioGrupoId && !$destinatarios->has($propietarioGrupoId)) {
                        $propietarioGrupo = \App\Models\User::find($propietarioGrupoId);
                        if ($propietarioGrupo) {
                            $destinatarios->put($propietarioGrupo->id, $propietarioGrupo);
                        }
                    }
                    // ────────────────────────────────────────────────────────

                    if ($destinatarios->isEmpty()) {
                        $this->warn("      ⚠️  No se encontró ningún destinatario para '{$tarea->name}'");
                        continue;
                    }

                    $this->line("   ⏰ Enviando notificación a {$destinatarios->count()} destinatario(s): vence en {$diasRestantes} día(s)");

                    foreach ($destinatarios as $usuario) {
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

                    // Registrar en timeline
                    $timelineUserId = $tarea->user_id
                        ?? $tarea->assignedUsers->first()?->id
                        ?? $propietarioGrupoId
                        ?? 1;

                    Timeline::create([
                        'tarea_id' => $tarea->id,
                        'user_id'  => $timelineUserId,
                        'action'   => 'notification_sent',
                        'details'  => [
                            'type'            => 'vencimiento_proximo',
                            'dias_restantes'  => $diasRestantes,
                            'notified_users'  => $destinatarios->count(),
                        ],
                    ]);

                    $this->info("      📝 Notificación registrada en timeline");
                }
            }

            // ====================================================
            // 2️⃣  TAREAS VENCIDAS
            // ====================================================
            $this->info('');
            $this->info('❌ Verificando tareas vencidas...');

            $tareasVencidas = Tareas::where('notifications_enabled', true)
                ->whereNotNull('due_date')
                ->whereNull('overdue_notification_sent_at')
                ->where('due_date', '<', $hoy->toDateString())
                ->where('status', '!=', 'completada')
                ->with(['assignedUsers', 'lista.grupo', 'grupo'])
                ->get();

            $this->info("   Encontradas {$tareasVencidas->count()} tareas vencidas sin notificar");

            foreach ($tareasVencidas as $tarea) {
                $this->line("   ❌ Tarea '{$tarea->name}' está vencida");

                // ─── Construir lista de destinatarios ───────────────────
                $destinatarios = $tarea->assignedUsers->keyBy('id');

                // 1. Creador de la tarea
                if ($tarea->user_id) {
                    $creadorTarea = \App\Models\User::find($tarea->user_id);
                    if ($creadorTarea && !$destinatarios->has($creadorTarea->id)) {
                        $destinatarios->put($creadorTarea->id, $creadorTarea);
                    }
                }

                // 2. Propietario del grupo
                $propietarioGrupoId = $tarea->grupo?->user_id;
                if ($propietarioGrupoId && !$destinatarios->has($propietarioGrupoId)) {
                    $propietarioGrupo = \App\Models\User::find($propietarioGrupoId);
                    if ($propietarioGrupo) {
                        $destinatarios->put($propietarioGrupo->id, $propietarioGrupo);
                    }
                }
                // ────────────────────────────────────────────────────────

                if ($destinatarios->isEmpty()) {
                    $this->warn("      ⚠️  No se encontró ningún destinatario para '{$tarea->name}'");
                    continue;
                }

                $this->line("      Enviando a {$destinatarios->count()} destinatario(s)...");

                foreach ($destinatarios as $usuario) {
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

                $timelineUserId = $tarea->user_id
                    ?? $tarea->assignedUsers->first()?->id
                    ?? $propietarioGrupoId
                    ?? 1;

                Timeline::create([
                    'tarea_id' => $tarea->id,
                    'user_id'  => $timelineUserId,
                    'action'   => 'notification_sent',
                    'details'  => [
                        'type'           => 'tarea_vencida',
                        'notified_users' => $destinatarios->count(),
                    ],
                ]);

                $this->info("      📝 Notificación de vencimiento registrada en timeline");
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