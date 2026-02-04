<?php

/**
 * Script para migrar datos de la tabla 'actividades' a 'activities'
 * 
 * INSTRUCCIONES DE USO:
 * 1. Guardar este archivo en: database/migrations/2025_11_25_000000_migrate_actividades_to_activities.php
 * 2. Ejecutar: php artisan migrate
 * 
 * Este script:
 * - Copia todos los registros de 'actividades' a 'activities'
 * - Preserva las fechas de creación
 * - No elimina los datos originales (por seguridad)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar que ambas tablas existan
        if (!DB::getSchemaBuilder()->hasTable('actividades') || 
            !DB::getSchemaBuilder()->hasTable('activities')) {
            echo "❌ Una o ambas tablas no existen\n";
            return;
        }

        echo "🔄 Iniciando migración de actividades...\n";

        // Obtener todos los registros de la tabla antigua
        $actividades = DB::table('actividades')->get();
        
        if ($actividades->isEmpty()) {
            echo "⚠️  No hay actividades para migrar\n";
            return;
        }

        echo "📊 Encontradas {$actividades->count()} actividades para migrar\n";

        $migrated = 0;
        $errors = 0;

        foreach ($actividades as $actividad) {
            try {
                // Verificar que la tarea y el usuario existan
                $tareaExists = DB::table('tareas')->where('id', $actividad->tarea_id)->exists();
                $userExists = DB::table('users')->where('id', $actividad->user_id)->exists();

                if (!$tareaExists || !$userExists) {
                    echo "⚠️  Skipping actividad ID {$actividad->id} (tarea o usuario no existe)\n";
                    continue;
                }

                // Verificar si ya existe este registro en activities
                $exists = DB::table('activities')
                    ->where('tarea_id', $actividad->tarea_id)
                    ->where('user_id', $actividad->user_id)
                    ->where('created_at', $actividad->created_at)
                    ->exists();

                if ($exists) {
                    echo "⏭️  Actividad ID {$actividad->id} ya existe en activities\n";
                    continue;
                }

                // Mapear el campo 'changes' a 'metadata'
                $metadata = null;
                if ($actividad->changes) {
                    // Si 'changes' es JSON, decodificarlo
                    $metadata = is_string($actividad->changes) 
                        ? $actividad->changes 
                        : json_encode($actividad->changes);
                }

                // Insertar en la nueva tabla
                DB::table('activities')->insert([
                    'user_id' => $actividad->user_id,
                    'tarea_id' => $actividad->tarea_id,
                    'type' => $actividad->type,
                    'description' => $actividad->description,
                    'metadata' => $metadata,
                    'created_at' => $actividad->created_at,
                    'updated_at' => $actividad->updated_at,
                ]);

                $migrated++;
                
                if ($migrated % 50 == 0) {
                    echo "✅ Migradas {$migrated} actividades...\n";
                }
            } catch (\Exception $e) {
                $errors++;
                echo "❌ Error al migrar actividad ID {$actividad->id}: {$e->getMessage()}\n";
            }
        }

        echo "\n✅ Migración completada!\n";
        echo "📊 Total migradas: {$migrated}\n";
        echo "❌ Errores: {$errors}\n";
        
        // Verificar conteo final
        $totalActivities = DB::table('activities')->count();
        echo "📈 Total de registros en tabla 'activities': {$totalActivities}\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Por seguridad, no eliminamos los datos en el rollback
        echo "⚠️  Rollback: Los datos no se eliminarán por seguridad.\n";
        echo "💡 Si deseas limpiar la tabla 'activities', hazlo manualmente:\n";
        echo "   TRUNCATE TABLE activities;\n";
    }
};