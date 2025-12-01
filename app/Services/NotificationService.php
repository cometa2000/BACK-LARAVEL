<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\tasks\Tareas;
use App\Models\tasks\Grupos;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * ✅ Crear una notificación de tarea asignada
     * 
     * @param int $userId - ID del usuario que recibirá la notificación
     * @param int $fromUserId - ID del usuario que asignó la tarea
     * @param int $tareaId - ID de la tarea
     * @param int|null $grupoId - ID del grupo (opcional)
     * @param string $tareaNombre - Nombre de la tarea
     * @param string|null $grupoNombre - Nombre del grupo (opcional)
     * @param string $asignadorNombre - Nombre completo del asignador
     */
    public static function tareaAsignada(
        int $userId,
        int $fromUserId,
        int $tareaId,
        ?int $grupoId,
        string $tareaNombre,
        ?string $grupoNombre,
        string $asignadorNombre
    ) {
        try {
            // No enviar notificación si el asignador y asignado son la misma persona
            if ($fromUserId === $userId) {
                return null;
            }

            $message = $grupoNombre 
                ? "{$asignadorNombre} te asignó la tarea \"{$tareaNombre}\" en {$grupoNombre}"
                : "{$asignadorNombre} te ha asignado la tarea: {$tareaNombre}";

            return Notification::create([
                'user_id' => $userId,
                'from_user_id' => $fromUserId,
                'tarea_id' => $tareaId,
                'grupo_id' => $grupoId,
                'type' => 'task_assigned',
                'title' => 'Nueva tarea asignada',
                'message' => $message,
                'data' => [
                    'tarea_nombre' => $tareaNombre,
                    'grupo_nombre' => $grupoNombre,
                    'asignador_nombre' => $asignadorNombre,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación de tarea asignada: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Crear una notificación de tarea completada
     * 
     * @param int $userId - ID del usuario que recibirá la notificación
     * @param int $fromUserId - ID del usuario que completó la tarea
     * @param int $tareaId - ID de la tarea
     * @param int|null $grupoId - ID del grupo (opcional)
     * @param string $tareaNombre - Nombre de la tarea
     * @param string|null $grupoNombre - Nombre del grupo (opcional)
     * @param string $completadorNombre - Nombre completo del completador
     */
    public static function tareaCompletada(
        int $userId,
        int $fromUserId,
        int $tareaId,
        ?int $grupoId,
        string $tareaNombre,
        ?string $grupoNombre,
        string $completadorNombre
    ) {
        try {
            // No enviar notificación si el completador y receptor son la misma persona
            if ($fromUserId === $userId) {
                return null;
            }

            $message = $grupoNombre
                ? "{$completadorNombre} ha completado la tarea \"{$tareaNombre}\" en {$grupoNombre}"
                : "{$completadorNombre} ha completado la tarea: {$tareaNombre}";

            return Notification::create([
                'user_id' => $userId,
                'from_user_id' => $fromUserId,
                'tarea_id' => $tareaId,
                'grupo_id' => $grupoId,
                'type' => 'task_completed',
                'title' => 'Tarea completada',
                'message' => $message,
                'data' => [
                    'tarea_nombre' => $tareaNombre,
                    'grupo_nombre' => $grupoNombre,
                    'completador_nombre' => $completadorNombre,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación de tarea completada: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Crear una notificación de grupo creado
     */
    public static function grupoCreado(Grupos $grupo, User $creador)
    {
        try {
            return Notification::create([
                'user_id' => $creador->id,
                'from_user_id' => $creador->id,
                'grupo_id' => $grupo->id,
                'type' => 'group_created',
                'title' => 'Grupo creado exitosamente',
                'message' => "Has creado el grupo: {$grupo->name}",
                'data' => [
                    'grupo_name' => $grupo->name,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación de grupo creado: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Crear una notificación de grupo compartido (para el propietario)
     */
    public static function grupoCompartidoPropietario(Grupos $grupo, User $propietario, array $usuariosCompartidos)
    {
        try {
            $nombresUsuarios = collect($usuariosCompartidos)->pluck('name')->join(', ');
            $cantidadUsuarios = count($usuariosCompartidos);

            return Notification::create([
                'user_id' => $propietario->id,
                'from_user_id' => $propietario->id,
                'grupo_id' => $grupo->id,
                'type' => 'group_shared_owner',
                'title' => 'Grupo compartido',
                'message' => "Has compartido el grupo '{$grupo->name}' con {$cantidadUsuarios} usuario(s)",
                'data' => [
                    'grupo_name' => $grupo->name,
                    'usuarios_compartidos' => $nombresUsuarios,
                    'cantidad_usuarios' => $cantidadUsuarios,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación de grupo compartido propietario: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Crear una notificación de grupo compartido (para el invitado)
     */
    public static function grupoCompartidoInvitado(Grupos $grupo, User $propietario, User $invitado)
    {
        try {
            // No enviar notificación si el propietario y el invitado son la misma persona
            if ($propietario->id === $invitado->id) {
                return null;
            }

            return Notification::create([
                'user_id' => $invitado->id,
                'from_user_id' => $propietario->id,
                'grupo_id' => $grupo->id,
                'type' => 'group_shared_invited',
                'title' => 'Te han compartido un grupo',
                'message' => "{$propietario->name} te ha compartido el grupo: {$grupo->name}",
                'data' => [
                    'grupo_name' => $grupo->name,
                    'propietario_name' => $propietario->name,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación de grupo compartido invitado: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Crear una notificación de tarea próxima a vencer
     */
    public static function tareaVencimientoProximo(Tareas $tarea, User $usuario, int $diasRestantes)
    {
        try {
            return Notification::create([
                'user_id' => $usuario->id,
                'tarea_id' => $tarea->id,
                'grupo_id' => $tarea->grupo_id,
                'type' => 'task_due_soon',
                'title' => 'Tarea próxima a vencer',
                'message' => "La tarea '{$tarea->name}' vence en {$diasRestantes} día(s)",
                'data' => [
                    'tarea_name' => $tarea->name,
                    'dias_restantes' => $diasRestantes,
                    'due_date' => $tarea->due_date?->format('Y-m-d'),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación de vencimiento próximo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Crear una notificación de tarea vencida
     */
    public static function tareaVencida(Tareas $tarea, User $usuario)
    {
        try {
            return Notification::create([
                'user_id' => $usuario->id,
                'tarea_id' => $tarea->id,
                'grupo_id' => $tarea->grupo_id,
                'type' => 'task_overdue',
                'title' => 'Tarea vencida',
                'message' => "La tarea '{$tarea->name}' ha vencido",
                'data' => [
                    'tarea_name' => $tarea->name,
                    'due_date' => $tarea->due_date?->format('Y-m-d'),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación de tarea vencida: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Crear una notificación de cambio de permisos
     */
    public static function permisosCambiados(Grupos $grupo, User $propietario, User $afectado, string $nuevoPermiso)
    {
        try {
            // No enviar notificación si son la misma persona
            if ($propietario->id === $afectado->id) {
                return null;
            }

            $permisoTexto = $nuevoPermiso === 'write' ? 'completos' : 'solo lectura';

            return Notification::create([
                'user_id' => $afectado->id,
                'from_user_id' => $propietario->id,
                'grupo_id' => $grupo->id,
                'type' => 'permission_changed',
                'title' => 'Permisos actualizados',
                'message' => "Tus permisos en el grupo '{$grupo->name}' han sido cambiados a: {$permisoTexto}",
                'data' => [
                    'grupo_name' => $grupo->name,
                    'nuevo_permiso' => $nuevoPermiso,
                    'propietario_name' => $propietario->name,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación de permisos cambiados: ' . $e->getMessage());
            return null;
        }
    }
}