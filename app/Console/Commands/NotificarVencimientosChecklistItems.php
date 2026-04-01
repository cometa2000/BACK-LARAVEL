<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\tasks\ChecklistItem;
use App\Models\tasks\Checklist;
use App\Models\tasks\Tareas;
use App\Models\tasks\Grupos;
use App\Mail\ChecklistItemVencimientoMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificarVencimientosChecklistItems extends Command
{
    /**
     * Nombre y firma del comando.
     * Ejecutar: php artisan checklist:notificar-vencimientos
     *
     * Programar en app/Console/Kernel.php:
     *   $schedule->command('checklist:notificar-vencimientos')->dailyAt('08:00');
     */
    protected $signature   = 'checklist:notificar-vencimientos';
    protected $description  = 'Envía correos de vencimiento próximo y vencido a los asignados de checklist items';

    public function handle(): int
    {
        $hoy           = Carbon::today();
        $limite        = Carbon::today()->addDays(3); // Notificar con hasta 3 días de anticipación
        $enviados      = 0;
        $errores       = 0;

        $this->info("📅 Revisando vencimientos de checklist items — {$hoy->format('d/m/Y')}");

        // Items NO completados con fecha de vencimiento dentro del rango de alerta
        $items = ChecklistItem::with(['assignedUsers', 'checklist.tarea'])
            ->whereNotNull('due_date')
            ->where('completed', false)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($hoy, $limite) {
                // Vencidos (hasta 7 días atrás para no spam infinito)
                $q->whereBetween('due_date', [Carbon::today()->subDays(7), $hoy->copy()->subDay()])
                  // O por vencer (hoy hasta +3 días)
                  ->orWhereBetween('due_date', [$hoy, $limite]);
            })
            ->get();

        $this->info("📋 Items encontrados: {$items->count()}");

        foreach ($items as $item) {
            $checklist = $item->checklist;
            if (!$checklist) continue;

            $tarea = $checklist->tarea;
            if (!$tarea) continue;

            $grupo = Grupos::find($tarea->grupo_id);
            if (!$grupo) continue;

            $dueDate       = Carbon::parse($item->due_date);
            $diasRestantes = $hoy->diffInDays($dueDate, false); // negativo si vencido

            foreach ($item->assignedUsers as $usuario) {
                try {
                    Mail::to($usuario->email)->send(new ChecklistItemVencimientoMail(
                        $usuario->name,
                        $item->name,
                        $checklist->name,
                        $tarea->name,
                        $grupo->name,
                        $grupo->id,
                        $item->due_date->format('Y-m-d'),
                        (int) $diasRestantes
                    ));

                    $tag = $diasRestantes <= 0 ? '🔴 VENCIDO' : "🟡 {$diasRestantes}d";
                    $this->line("  ✅ [{$tag}] '{$item->name}' → {$usuario->email}");
                    $enviados++;

                } catch (\Exception $e) {
                    $this->error("  ❌ Error enviando a {$usuario->email}: {$e->getMessage()}");
                    Log::error('❌ Error NotificarVencimientosChecklistItems', [
                        'item_id' => $item->id,
                        'user_id' => $usuario->id,
                        'error'   => $e->getMessage(),
                    ]);
                    $errores++;
                }
            }
        }

        $this->info("✅ Correos enviados: {$enviados} | ❌ Errores: {$errores}");
        Log::info("checklist:notificar-vencimientos completado", ['enviados' => $enviados, 'errores' => $errores]);

        return Command::SUCCESS;
    }
}