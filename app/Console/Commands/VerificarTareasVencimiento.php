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
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tareas:verificar-vencimiento';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica las tareas próximas a vencer y las vencidas, y envía notificaciones a los miembros asignados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando verificación de tareas...');
        
        $now = Carbon::now();
        $notificacionesEnviadas = 0;
        
        try {
            // ========================================
            // 1️⃣ VERIFICAR TAREAS PRÓXIMAS A VENCER
            // ========================================
            $this->info('📅 Verificando tareas próximas a vencer...');
            
            $tareasProximas = Tareas::where('notifications_enabled', true)
                ->whereNotNull('due_date')
                ->whereNotNull('notification_days_before')
                ->whereNull('notification_sent_at')
                ->where('due_date', '>', $now)
                ->where('status', '!=', 'completada')
                ->with(['assignedUsers', 'lista.grupo'])
                ->get();
            
            $this->info("   Encontradas {$tareasProximas->count()} tareas con notificaciones habilitadas");
            
            foreach ($tareasProximas as $tarea) {
                $dueDate = Carbon::parse($tarea->due_date);
                $diasRestantes = $now->diffInDays($dueDate, false);
                
                // Si faltan exactamente los días configurados (o menos), enviar notificación
                if ($diasRestantes <= $tarea->notification_days_before && $diasRestantes >= 0) {
                    $this->line("   ⏰ Tarea '{$tarea->name}' vence en {$diasRestantes} día(s)");
                    
                    // Enviar a miembros asignados
                    if ($tarea->assignedUsers && $tarea->assignedUsers->count() > 0) {
                        foreach ($tarea->assignedUsers as $usuario) {
                            try {
                                Mail::to($usuario->email)->send(
                                    new TareaVencimientoProximoMail($tarea, $usuario, $diasRestantes)
                                );
                                
                                $this->info("      ✅ Email enviado a {$usuario->email}");
                                $notificacionesEnviadas++;
                            } catch (\Exception $e) {
                                $this->error("      ❌ Error enviando a {$usuario->email}: {$e->getMessage()}");
                                Log::error("Error enviando notificación próxima a vencer: {$e->getMessage()}");
                            }
                        }
                        
                        // Marcar notificación como enviada
                        $tarea->notification_sent_at = $now;
                        $tarea->save();
                        
                        // Registrar en timeline
                        Timeline::create([
                            'tarea_id' => $tarea->id,
                            'user_id' => $tarea->user_id,
                            'action' => 'notification_sent',
                            'details' => [
                                'type' => 'vencimiento_proximo',
                                'dias_restantes' => $diasRestantes,
                                'notified_users' => $tarea->assignedUsers->count()
                            ]
                        ]);
                        
                        $this->info("      📝 Notificación registrada en timeline");
                    } else {
                        $this->warn("      ⚠️  No hay miembros asignados a esta tarea");
                    }
                }
            }
            
            // ========================================
            // 2️⃣ VERIFICAR TAREAS VENCIDAS
            // ========================================
            $this->info('');
            $this->info('❌ Verificando tareas vencidas...');
            
            $tareasVencidas = Tareas::where('notifications_enabled', true)
                ->whereNotNull('due_date')
                ->whereNull('overdue_notification_sent_at')
                ->where('due_date', '<', $now)
                ->where('status', '!=', 'completada')
                ->with(['assignedUsers', 'lista.grupo'])
                ->get();
            
            $this->info("   Encontradas {$tareasVencidas->count()} tareas vencidas sin notificar");
            
            foreach ($tareasVencidas as $tarea) {
                $this->line("   ❌ Tarea '{$tarea->name}' está vencida");
                
                // Enviar a miembros asignados
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
                            Log::error("Error enviando notificación de tarea vencida: {$e->getMessage()}");
                        }
                    }
                    
                    // Marcar notificación de vencimiento como enviada
                    $tarea->overdue_notification_sent_at = $now;
                    $tarea->save();
                    
                    // Registrar en timeline
                    Timeline::create([
                        'tarea_id' => $tarea->id,
                        'user_id' => $tarea->user_id,
                        'action' => 'notification_sent',
                        'details' => [
                            'type' => 'tarea_vencida',
                            'notified_users' => $tarea->assignedUsers->count()
                        ]
                    ]);
                    
                    $this->info("      📝 Notificación de vencimiento registrada en timeline");
                } else {
                    $this->warn("      ⚠️  No hay miembros asignados a esta tarea");
                }
            }
            
            // ========================================
            // 📊 RESUMEN
            // ========================================
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
            
            Log::error('Error en VerificarTareasVencimiento: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}