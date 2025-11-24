<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Activity;

/**
 * Comando para migrar datos de la tabla 'actividades' a 'activities'
 * 
 * USO:
 * php artisan activities:migrate
 * 
 * OPCIONES:
 * --dry-run : Mostrar qué se migrará sin hacer cambios
 * --limit=N : Limitar el número de registros a migrar
 */
class MigrateActivitiesCommand extends Command
{
    protected $signature = 'activities:migrate 
                            {--dry-run : Mostrar qué se migrará sin hacer cambios}
                            {--limit= : Limitar el número de registros a migrar}
                            {--force : Forzar la migración sin confirmación}';
    
    protected $description = 'Migra datos de la tabla actividades a activities';

    private $migratedCount = 0;
    private $errorCount = 0;
    private $skippedCount = 0;

    public function handle()
    {
        $this->info('🔄 INICIANDO MIGRACIÓN DE ACTIVIDADES');
        $this->newLine();

        // Verificar que ambas tablas existen
        if (!$this->verifyTables()) {
            return Command::FAILURE;
        }

        // Obtener registros a migrar
        $actividades = $this->getActivitiesToMigrate();
        
        if ($actividades->isEmpty()) {
            $this->warn('⚠️  No hay actividades para migrar');
            return Command::SUCCESS;
        }

        $this->info("📊 Se encontraron {$actividades->count()} actividades para migrar");
        $this->newLine();

        // Modo dry-run
        if ($this->option('dry-run')) {
            $this->dryRun($actividades);
            return Command::SUCCESS;
        }

        // Confirmar migración
        if (!$this->option('force') && !$this->confirm('¿Deseas continuar con la migración?')) {
            $this->warn('❌ Migración cancelada');
            return Command::FAILURE;
        }

        // Realizar migración
        $this->migrate($actividades);

        // Resumen
        $this->showSummary();

        return Command::SUCCESS;
    }

    /**
     * Verificar que las tablas necesarias existen
     */
    private function verifyTables(): bool
    {
        $this->info('🔍 Verificando tablas...');

        if (!Schema::hasTable('actividades')) {
            $this->error('❌ La tabla "actividades" no existe');
            return false;
        }

        if (!Schema::hasTable('activities')) {
            $this->error('❌ La tabla "activities" no existe');
            return false;
        }

        $this->line('  ✅ Tabla "actividades" existe');
        $this->line('  ✅ Tabla "activities" existe');
        $this->newLine();

        return true;
    }

    /**
     * Obtener actividades a migrar
     */
    private function getActivitiesToMigrate()
    {
        $query = DB::table('actividades')
            ->whereNotNull('tarea_id')
            ->whereNotNull('user_id')
            ->orderBy('created_at', 'asc');

        if ($limit = $this->option('limit')) {
            $query->limit((int)$limit);
        }

        return $query->get();
    }

    /**
     * Modo dry-run: mostrar qué se migrará
     */
    private function dryRun($actividades)
    {
        $this->warn('🔍 MODO DRY-RUN: No se realizarán cambios');
        $this->newLine();

        $types = $actividades->groupBy('type')->map->count();

        $this->info('📋 Resumen por tipo:');
        foreach ($types as $type => $count) {
            $this->line("  • {$type}: {$count} registros");
        }

        $this->newLine();
        $this->info('Mostrando primeros 5 registros:');
        
        foreach ($actividades->take(5) as $actividad) {
            $this->line("  ID: {$actividad->id} | Tipo: {$actividad->type} | Tarea: {$actividad->tarea_id}");
        }

        $this->newLine();
        $this->info('💡 Ejecuta sin --dry-run para migrar los datos');
    }

    /**
     * Realizar la migración
     */
    private function migrate($actividades)
    {
        $this->info('🚀 Iniciando migración...');
        $bar = $this->output->createProgressBar($actividades->count());
        $bar->start();

        foreach ($actividades as $actividad) {
            try {
                // Verificar si ya existe
                $exists = Activity::where('tarea_id', $actividad->tarea_id)
                    ->where('user_id', $actividad->user_id)
                    ->where('type', $actividad->type)
                    ->where('created_at', $actividad->created_at)
                    ->exists();

                if ($exists) {
                    $this->skippedCount++;
                    $bar->advance();
                    continue;
                }

                // Mapear campos
                $data = [
                    'user_id' => $actividad->user_id,
                    'tarea_id' => $actividad->tarea_id,
                    'type' => $this->mapActivityType($actividad->type),
                    'description' => $actividad->description,
                    'metadata' => $actividad->changes ? json_decode($actividad->changes, true) : null,
                    'created_at' => $actividad->created_at,
                    'updated_at' => $actividad->updated_at,
                ];

                Activity::create($data);
                $this->migratedCount++;

            } catch (\Exception $e) {
                $this->errorCount++;
                \Log::error("Error al migrar actividad ID {$actividad->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    /**
     * Mapear tipos de actividad de la tabla antigua a la nueva
     */
    private function mapActivityType($oldType): string
    {
        $typeMap = [
            'created' => 'created',
            'updated' => 'updated',
            'deleted' => 'deleted',
            'completed' => 'completed',
            'status_changed' => 'status_change',
            'assigned' => 'assignment',
            'commented' => 'comment',
            'attachment_added' => 'attachment',
            'due_date_changed' => 'due_date',
            'checklist_added' => 'checklist',
        ];

        return $typeMap[$oldType] ?? 'updated';
    }

    /**
     * Mostrar resumen de la migración
     */
    private function showSummary()
    {
        $this->newLine();
        $this->info('📊 RESUMEN DE LA MIGRACIÓN:');
        $this->line("  ✅ Registros migrados: {$this->migratedCount}");
        $this->line("  ⏭️  Registros omitidos (duplicados): {$this->skippedCount}");
        $this->line("  ❌ Errores: {$this->errorCount}");

        $total = $this->migratedCount + $this->skippedCount + $this->errorCount;
        $this->line("  📈 Total procesado: {$total}");

        $this->newLine();

        if ($this->errorCount > 0) {
            $this->warn('⚠️  Hubo errores durante la migración. Revisa el log para más detalles.');
        } else {
            $this->info('✅ Migración completada exitosamente!');
        }

        // Mostrar estado actual
        $this->newLine();
        $this->info('📈 Estado actual de las tablas:');
        $this->line('  • actividades (antigua): ' . DB::table('actividades')->count() . ' registros');
        $this->line('  • activities (nueva): ' . Activity::count() . ' registros');

        $this->newLine();
        $this->info('💡 Próximos pasos:');
        $this->line('  1. Verifica que los datos se migraron correctamente');
        $this->line('  2. Prueba el sistema de actividades en el frontend');
        $this->line('  3. Si todo funciona, considera eliminar la tabla antigua');
    }
}