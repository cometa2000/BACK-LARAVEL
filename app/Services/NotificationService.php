<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\tasks\Tareas;
use App\Models\tasks\Grupos;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    // =========================================================================
    // TAREAS
    // =========================================================================

    /**
     * ✅ Crear una notificación de tarea asignada
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
            if ($fromUserId === $userId) {
                return null;
            }

            $message = $grupoNombre
                ? "{$asignadorNombre} te asignó la tarea \"{$tareaNombre}\" en {$grupoNombre}"
                : "{$asignadorNombre} te ha asignado la tarea: {$tareaNombre}";

            return Notification::create([
                'user_id'      => $userId,
                'from_user_id' => $fromUserId,
                'tarea_id'     => $tareaId,
                'grupo_id'     => $grupoId,
                'type'         => 'task_assigned',
                'title'        => 'Nueva tarea asignada',
                'message'      => $message,
                'data'         => [
                    'tarea_nombre'     => $tareaNombre,
                    'grupo_nombre'     => $grupoNombre,
                    'grupo_id'         => $grupoId,
                    'asignador_nombre' => $asignadorNombre,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación de tarea asignada: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Crear una notificación de tarea completada
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
            if ($fromUserId === $userId) {
                return null;
            }

            $message = $grupoNombre
                ? "{$completadorNombre} ha completado la tarea \"{$tareaNombre}\" en {$grupoNombre}"
                : "{$completadorNombre} ha completado la tarea: {$tareaNombre}";

            return Notification::create([
                'user_id'      => $userId,
                'from_user_id' => $fromUserId,
                'tarea_id'     => $tareaId,
                'grupo_id'     => $grupoId,
                'type'         => 'task_completed',
                'title'        => 'Tarea completada',
                'message'      => $message,
                'data'         => [
                    'tarea_nombre'       => $tareaNombre,
                    'grupo_nombre'       => $grupoNombre,
                    'grupo_id'           => $grupoId,
                    'completador_nombre' => $completadorNombre,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación de tarea completada: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Crear una notificación de tarea próxima a vencer (Artisan command)
     */
    public static function tareaVencimientoProximo(Tareas $tarea, User $usuario, int $diasRestantes)
    {
        try {
            $mensaje = $diasRestantes === 1
                ? "La tarea \"{$tarea->name}\" vence mañana"
                : "La tarea \"{$tarea->name}\" vence en {$diasRestantes} días";

            return Notification::create([
                'user_id'      => $usuario->id,
                'from_user_id' => null,
                'tarea_id'     => $tarea->id,
                'grupo_id'     => $tarea->grupo_id,
                'type'         => 'task_due_soon',
                'title'        => 'Tarea próxima a vencer',
                'message'      => $mensaje,
                'data'         => [
                    'tarea_name'     => $tarea->name,
                    'grupo_id'       => $tarea->grupo_id,
                    'dias_restantes' => $diasRestantes,
                    'due_date'       => $tarea->due_date?->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación de vencimiento próximo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Crear una notificación de tarea vencida (Artisan command)
     */
    public static function tareaVencida(Tareas $tarea, User $usuario)
    {
        try {
            return Notification::create([
                'user_id'      => $usuario->id,
                'from_user_id' => null,
                'tarea_id'     => $tarea->id,
                'grupo_id'     => $tarea->grupo_id,
                'type'         => 'task_overdue',
                'title'        => 'Tarea vencida',
                'message'      => "La tarea \"{$tarea->name}\" ha superado su fecha de vencimiento",
                'data'         => [
                    'tarea_name' => $tarea->name,
                    'grupo_id'   => $tarea->grupo_id,
                    'due_date'   => $tarea->due_date?->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación de tarea vencida: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // REACTIVACIONES
    // =========================================================================

    /**
     * ✅ Notificación al solicitante: "tu solicitud fue enviada al propietario"
     */
    public static function reactivacionSolicitante(
        User   $solicitante,
        User   $propietario,
        Tareas $tarea,
        Grupos $grupo
    ) {
        try {
            return Notification::create([
                'user_id'      => $solicitante->id,
                'from_user_id' => $propietario->id,
                'tarea_id'     => $tarea->id,
                'grupo_id'     => $grupo->id,
                'type'         => 'reactivacion_solicitante',
                'title'        => 'Solicitud de reactivación enviada',
                'message'      => "Tu solicitud para reactivar \"{$tarea->name}\" fue enviada a {$propietario->name}",
                'data'         => [
                    'tarea_name' => $tarea->name,
                    'grupo_id'   => $grupo->id,
                    'grupo_name' => $grupo->name,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación reactivacionSolicitante: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Notificación al propietario: "recibiste una solicitud de reactivación"
     */
    public static function reactivacionPropietario(
        User   $propietario,
        User   $solicitante,
        Tareas $tarea,
        Grupos $grupo
    ) {
        try {
            return Notification::create([
                'user_id'      => $propietario->id,
                'from_user_id' => $solicitante->id,
                'tarea_id'     => $tarea->id,
                'grupo_id'     => $grupo->id,
                'type'         => 'reactivacion_propietario',
                'title'        => 'Solicitud de reactivación recibida',
                'message'      => "{$solicitante->name} solicita reactivar la tarea \"{$tarea->name}\"",
                'data'         => [
                    'tarea_name'       => $tarea->name,
                    'grupo_id'         => $grupo->id,
                    'grupo_name'       => $grupo->name,
                    'solicitante_name' => $solicitante->name,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación reactivacionPropietario: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Notificación a un miembro asignado: "la tarea fue reactivada"
     */
    public static function tareaReactivada(
        User   $miembro,
        User   $propietario,
        Tareas $tarea,
        Grupos $grupo,
        string $nuevaFecha,
        string $accion = 'editada'
    ) {
        try {
            $mensaje = $accion === 'eliminada'
                ? "{$propietario->name} reactivó \"{$tarea->name}\" (fecha de vencimiento eliminada)"
                : "{$propietario->name} reactivó \"{$tarea->name}\" con nueva fecha: {$nuevaFecha}";

            return Notification::create([
                'user_id'      => $miembro->id,
                'from_user_id' => $propietario->id,
                'tarea_id'     => $tarea->id,
                'grupo_id'     => $grupo->id,
                'type'         => 'tarea_reactivada',
                'title'        => '¡Tarea reactivada!',
                'message'      => $mensaje,
                'data'         => [
                    'tarea_name'  => $tarea->name,
                    'grupo_id'    => $grupo->id,
                    'grupo_name'  => $grupo->name,
                    'nueva_fecha' => $nuevaFecha,
                    'accion'      => $accion,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación tareaReactivada: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Notificación al propietario: "reactivaste la tarea exitosamente"
     */
    public static function reactivacionConfirmadaPropietario(
        User   $propietario,
        Tareas $tarea,
        Grupos $grupo,
        string $nuevaFecha,
        int    $totalMiembros,
        string $accion = 'editada'
    ) {
        try {
            $mensaje = $accion === 'eliminada'
                ? "Reactivaste \"{$tarea->name}\" eliminando la fecha. {$totalMiembros} miembro(s) notificado(s)"
                : "Reactivaste \"{$tarea->name}\" con nueva fecha {$nuevaFecha}. {$totalMiembros} miembro(s) notificado(s)";

            return Notification::create([
                'user_id'      => $propietario->id,
                'from_user_id' => $propietario->id,
                'tarea_id'     => $tarea->id,
                'grupo_id'     => $grupo->id,
                'type'         => 'reactivacion_confirmada',
                'title'        => 'Tarea reactivada exitosamente',
                'message'      => $mensaje,
                'data'         => [
                    'tarea_name'     => $tarea->name,
                    'grupo_id'       => $grupo->id,
                    'grupo_name'     => $grupo->name,
                    'nueva_fecha'    => $nuevaFecha,
                    'total_miembros' => $totalMiembros,
                    'accion'         => $accion,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación reactivacionConfirmadaPropietario: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // CHECKLIST ITEMS
    // =========================================================================

    /**
     * ✅ Notificación al asignado: "te asignaron a un elemento de checklist"
     */
    public static function checklistItemAsignado(
        User   $asignado,
        User   $asignador,
        Tareas $tarea,
        Grupos $grupo,
        string $nombreItem,
        string $nombreChecklist,
        ?string $fechaVencimiento = null
    ) {
        try {
            if ($asignador->id === $asignado->id) {
                return null;
            }

            return Notification::create([
                'user_id'      => $asignado->id,
                'from_user_id' => $asignador->id,
                'tarea_id'     => $tarea->id,
                'grupo_id'     => $grupo->id,
                'type'         => 'checklist_item_assigned',
                'title'        => 'Nueva asignación en checklist',
                'message'      => "{$asignador->name} te asignó el elemento \"{$nombreItem}\" en \"{$tarea->name}\"",
                'data'         => [
                    'tarea_name'        => $tarea->name,
                    'grupo_id'          => $grupo->id,
                    'grupo_name'        => $grupo->name,
                    'item_name'         => $nombreItem,
                    'checklist_name'    => $nombreChecklist,
                    'fecha_vencimiento' => $fechaVencimiento,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación checklistItemAsignado: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Notificación al asignador: confirmación de asignación de miembros al item
     */
    public static function checklistItemAsignadoConfirmacion(
        User   $asignador,
        Tareas $tarea,
        Grupos $grupo,
        string $nombreItem,
        string $nombreChecklist,
        int    $cantidadAsignados
    ) {
        try {
            $mensaje = $cantidadAsignados === 1
                ? "Asignaste 1 colaborador al elemento \"{$nombreItem}\""
                : "Asignaste {$cantidadAsignados} colaboradores al elemento \"{$nombreItem}\"";

            return Notification::create([
                'user_id'      => $asignador->id,
                'from_user_id' => $asignador->id,
                'tarea_id'     => $tarea->id,
                'grupo_id'     => $grupo->id,
                'type'         => 'checklist_item_assigned_owner',
                'title'        => 'Miembros asignados al checklist',
                'message'      => $mensaje,
                'data'         => [
                    'tarea_name'     => $tarea->name,
                    'grupo_id'       => $grupo->id,
                    'grupo_name'     => $grupo->name,
                    'item_name'      => $nombreItem,
                    'checklist_name' => $nombreChecklist,
                    'cantidad'       => $cantidadAsignados,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación checklistItemAsignadoConfirmacion: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Notificación de item de checklist próximo a vencer / vencido (Artisan)
     */
    public static function checklistItemVencimiento(
        User   $usuario,
        Tareas $tarea,
        Grupos $grupo,
        string $nombreItem,
        string $nombreChecklist,
        int    $diasRestantes
    ) {
        try {
            $esVencido = $diasRestantes <= 0;

            if ($esVencido) {
                $titulo  = 'Elemento de checklist vencido';
                $mensaje = "El elemento \"{$nombreItem}\" en \"{$tarea->name}\" ha vencido";
            } else {
                $titulo  = 'Elemento de checklist próximo a vencer';
                $mensaje = "El elemento \"{$nombreItem}\" en \"{$tarea->name}\" vence en {$diasRestantes} día(s)";
            }

            return Notification::create([
                'user_id'      => $usuario->id,
                'from_user_id' => null,
                'tarea_id'     => $tarea->id,
                'grupo_id'     => $grupo->id,
                'type'         => 'checklist_item_due',
                'title'        => $titulo,
                'message'      => $mensaje,
                'data'         => [
                    'tarea_name'     => $tarea->name,
                    'grupo_id'       => $grupo->id,
                    'grupo_name'     => $grupo->name,
                    'item_name'      => $nombreItem,
                    'checklist_name' => $nombreChecklist,
                    'dias_restantes' => $diasRestantes,
                    'es_vencido'     => $esVencido,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación checklistItemVencimiento: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // GRUPOS
    // =========================================================================

    /**
     * ✅ Crear una notificación de grupo creado
     */
    public static function grupoCreado(Grupos $grupo, User $creador)
    {
        try {
            return Notification::create([
                'user_id'      => $creador->id,
                'from_user_id' => $creador->id,
                'tarea_id'     => null,
                'grupo_id'     => $grupo->id,
                'type'         => 'group_created',
                'title'        => 'Grupo creado exitosamente',
                'message'      => "Has creado el grupo \"{$grupo->name}\"",
                'data'         => [
                    'grupo_name'     => $grupo->name,
                    'grupo_id'       => $grupo->id,
                    'workspace_id'   => $grupo->workspace_id,
                    'workspace_name' => $grupo->workspace?->name ?? '',
                ],
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
            $cantidadUsuarios = count($usuariosCompartidos);

            $mensaje = $cantidadUsuarios === 1
                ? "Compartiste el grupo \"{$grupo->name}\" con 1 colaborador"
                : "Compartiste el grupo \"{$grupo->name}\" con {$cantidadUsuarios} colaboradores";

            return Notification::create([
                'user_id'      => $propietario->id,
                'from_user_id' => $propietario->id,
                'tarea_id'     => null,
                'grupo_id'     => $grupo->id,
                'type'         => 'group_shared_owner',
                'title'        => 'Grupo compartido',
                'message'      => $mensaje,
                'data'         => [
                    'grupo_name'     => $grupo->name,
                    'grupo_id'       => $grupo->id,
                    'workspace_id'   => $grupo->workspace_id,
                    'workspace_name' => $grupo->workspace?->name ?? '',
                    'cantidad'       => $cantidadUsuarios,
                ],
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
            if ($propietario->id === $invitado->id) {
                return null;
            }

            return Notification::create([
                'user_id'      => $invitado->id,
                'from_user_id' => $propietario->id,
                'tarea_id'     => null,
                'grupo_id'     => $grupo->id,
                'type'         => 'group_shared_invited',
                'title'        => 'Te han compartido un grupo',
                'message'      => "{$propietario->name} te compartió el grupo \"{$grupo->name}\"",
                'data'         => [
                    'grupo_name'     => $grupo->name,
                    'grupo_id'       => $grupo->id,
                    'workspace_id'   => $grupo->workspace_id,
                    'workspace_name' => $grupo->workspace?->name ?? '',
                    'propietario'    => $propietario->name,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación de grupo compartido invitado: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // WORKSPACE
    // =========================================================================

    /**
     * ✅ Crear una notificación de workspace creado
     */
    public static function workspaceCreado(\App\Models\tasks\Workspace $workspace, User $creador)
    {
        try {
            return Notification::create([
                'user_id'      => $creador->id,
                'from_user_id' => $creador->id,
                'tarea_id'     => null,
                'grupo_id'     => null,
                'type'         => 'workspace_created',
                'title'        => 'Espacio de trabajo creado',
                'message'      => "Creaste el espacio de trabajo \"{$workspace->name}\" exitosamente",
                'data'         => [
                    'workspace_id'   => $workspace->id,
                    'workspace_name' => $workspace->name,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación de workspace creado: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // OTROS
    // =========================================================================

    /**
     * ✅ Crear una notificación de cambio de permisos
     */
    public static function permisosCambiados(Grupos $grupo, User $propietario, User $afectado, string $nuevoPermiso)
    {
        try {
            if ($propietario->id === $afectado->id) {
                return null;
            }

            $permisoTexto = $nuevoPermiso === 'write' ? 'completos' : 'solo lectura';

            return Notification::create([
                'user_id'      => $afectado->id,
                'from_user_id' => $propietario->id,
                'tarea_id'     => null,
                'grupo_id'     => $grupo->id,
                'type'         => 'permission_changed',
                'title'        => 'Permisos actualizados',
                'message'      => "Tus permisos en \"{$grupo->name}\" cambiaron a: {$permisoTexto}",
                'data'         => [
                    'grupo_name'    => $grupo->name,
                    'grupo_id'      => $grupo->id,
                    'workspace_id'  => $grupo->workspace_id,
                    'nuevo_permiso' => $nuevoPermiso,
                    'propietario'   => $propietario->name,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear notificación de permisos cambiados: ' . $e->getMessage());
            return null;
        }
    }
}