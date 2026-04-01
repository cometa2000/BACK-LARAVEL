<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ========================================
        // 🔔 VERIFICACIÓN DE TAREAS
        // ========================================
        
        // Opción 1: Ejecutar cada hora (recomendado para producción)
        $schedule->command('tareas:verificar-vencimiento')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();
        
        // Opción 2: Ejecutar varias veces al día (más preciso)
        // $schedule->command('tareas:verificar-vencimiento')
        //     ->twiceDaily(9, 18) // 9 AM y 6 PM
        //     ->withoutOverlapping()
        //     ->runInBackground();
        
        // Opción 3: Ejecutar cada 30 minutos (máxima precisión)
        // $schedule->command('tareas:verificar-vencimiento')
        //     ->everyThirtyMinutes()
        //     ->withoutOverlapping()
        //     ->runInBackground();
        
        // Opción 4: Ejecutar solo en días laborales de 8 AM a 6 PM
        // $schedule->command('tareas:verificar-vencimiento')
        //     ->hourly()
        //     ->weekdays()
        //     ->between('8:00', '18:00')
        //     ->withoutOverlapping()
        //     ->runInBackground();

        // ========================================
        //  VERIFICACIÓN DE ELEMENTOS VENCIDOS
        // ========================================
        $schedule->command('checklist:notificar-vencimientos')->dailyAt('08:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    
}
