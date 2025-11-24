<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Activity;
use App\Models\Notification;
use App\Models\tasks\Tareas;
use App\Models\User;

/**
 * Comando para diagnosticar el sistema de actividades y notificaciones
 * 
 * USO:
 * php artisan activities:diagnose
 */
class DiagnoseActivitiesCommand extends Command
{
    protected $signature = 'activities:diagnose';
    protected $description = 'Diagnostica el sistema de actividades y notificaciones';

    public function handle()
    {
        $this->info('🔍 DIAGNÓSTICO DEL SISTEMA DE ACTIVIDADES Y NOTIFICACIONES');
        $this->newLine();

        // 1. Verificar tablas
        $this->checkTables();
        $this->newLine();

        // 2. Verificar datos
        $this->checkData();
        $this->newLine();

        // 3. Verificar modelos
        $this->checkModels();
        $this->newLine();

        // 4. Verificar relaciones
        $this->checkRelations();
        $this->newLine();

        // 5. Verificar rutas
        $this->checkRoutes();
        $this->newLine();

        $this->info('✅ Diagnóstico completado');
    }

    private function checkTables()
    {
        $this->info('📊 Verificando tablas de base de datos...');
        
        $tables = [
            'activities' => 'Tabla de actividades (nueva)',
            'actividades' => 'Tabla de actividades (antigua)',
            'notifications' => 'Tabla de notificaciones',
            'tareas' => 'Tabla de tareas',
            'users' => 'Tabla de usuarios',
        ];

        foreach ($tables as $table => $description) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $this->line("  ✅ {$description}: {$count} registros");
            } else {
                $this->error("  ❌ {$description}: NO EXISTE");
            }
        }
    }

    private function checkData()
    {
        $this->info('📈 Analizando datos...');

        // Activities
        $activitiesCount = DB::table('activities')->count();
        $actividadesCount = Schema::hasTable('actividades') 
            ? DB::table('actividades')->count() 
            : 0;

        if ($activitiesCount === 0 && $actividadesCount > 0) {
            $this->warn("  ⚠️  PROBLEMA: La tabla 'activities' está vacía pero 'actividades' tiene {$actividadesCount} registros");
            $this->line("     💡 Ejecuta: php artisan migrate para migrar los datos");
        } elseif ($activitiesCount > 0) {
            $this->line("  ✅ Tabla 'activities': {$activitiesCount} registros");
        } else {
            $this->warn("  ⚠️  Ambas tablas de actividades están vacías");
        }

        // Notifications
        $notificationsCount = DB::table('notifications')->count();
        if ($notificationsCount === 0) {
            $this->warn("  ⚠️  La tabla 'notifications' está vacía");
            $this->line("     💡 Las notificaciones se crearán cuando ocurran eventos");
        } else {
            $this->line("  ✅ Tabla 'notifications': {$notificationsCount} registros");
        }

        // Tareas con miembros
        $tareasWithMembers = DB::table('tarea_user')->count();
        $this->line("  📊 Tareas con miembros asignados: {$tareasWithMembers}");
    }

    private function checkModels()
    {
        $this->info('🏗️  Verificando modelos...');

        // Verificar Activity
        try {
            $activity = new Activity();
            $fillable = $activity->getFillable();
            
            if (in_array('tarea_id', $fillable)) {
                $this->line("  ✅ Modelo Activity: configurado correctamente");
            } else {
                $this->error("  ❌ Modelo Activity: falta 'tarea_id' en fillable");
            }

            // Verificar relación
            $reflection = new \ReflectionClass($activity);
            if ($reflection->hasMethod('tarea')) {
                $this->line("  ✅ Relación 'tarea()' existe en Activity");
            } else {
                $this->error("  ❌ Relación 'tarea()' NO existe en Activity");
                $this->warn("     💡 Debe existir el método 'tarea()' (singular)");
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Error al verificar modelo Activity: {$e->getMessage()}");
        }

        // Verificar Notification
        try {
            $notification = new Notification();
            $fillable = $notification->getFillable();
            
            if (in_array('user_id', $fillable) && in_array('tarea_id', $fillable)) {
                $this->line("  ✅ Modelo Notification: configurado correctamente");
            } else {
                $this->error("  ❌ Modelo Notification: configuración incorrecta");
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Error al verificar modelo Notification: {$e->getMessage()}");
        }
    }

    private function checkRelations()
    {
        $this->info('🔗 Verificando relaciones...');

        try {
            // Intentar cargar una actividad con su tarea
            $activity = Activity::with('tarea', 'user')->first();
            
            if ($activity) {
                $this->line("  ✅ Relación Activity -> Tarea: funciona");
                $this->line("  ✅ Relación Activity -> User: funciona");
            } else {
                $this->warn("  ⚠️  No hay actividades para probar relaciones");
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Error en relaciones: {$e->getMessage()}");
            $this->warn("     💡 Verifica que el método sea 'tarea()' no 'tareas()'");
        }

        try {
            // Intentar cargar una notificación
            $notification = Notification::with('fromUser', 'tarea')->first();
            
            if ($notification) {
                $this->line("  ✅ Relación Notification -> User: funciona");
                $this->line("  ✅ Relación Notification -> Tarea: funciona");
            } else {
                $this->warn("  ⚠️  No hay notificaciones para probar relaciones");
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Error en relaciones de notificación: {$e->getMessage()}");
        }
    }

    private function checkRoutes()
    {
        $this->info('🛣️  Verificando rutas de API...');

        $routes = [
            'api/activities' => 'GET',
            'api/activities/tarea/{tareaId}' => 'GET',
            'api/notifications' => 'GET',
            'api/notifications/unread-count' => 'GET',
        ];

        $routeCollection = \Illuminate\Support\Facades\Route::getRoutes();

        foreach ($routes as $uri => $method) {
            $route = $routeCollection->match(
                \Illuminate\Http\Request::create($uri, $method)
            );
            
            if ($route) {
                $this->line("  ✅ Ruta {$method} {$uri}: existe");
            } else {
                $this->error("  ❌ Ruta {$method} {$uri}: NO existe");
            }
        }
    }
}