<?php

namespace App\Traits;

use App\Models\Activity;
use Illuminate\Support\Facades\Auth;

/**
 * Trait ActivityLogger
 * 
 * Este trait registra automáticamente las actividades cuando se crean,
 * actualizan o eliminan modelos que lo usen.
 * 
 * IMPORTANTE: El modelo debe tener 'tarea_id' o 'id' (si es Tarea)
 * 
 * Uso:
 * use ActivityLogger;
 */
trait ActivityLogger
{
    /**
     * Boot del trait
     */
    protected static function bootActivityLogger()
    {
        // Al crear una tarea
        static::created(function ($model) {
            $user = Auth::user();
            if (!$user) return;

            // Si el modelo ES una tarea
            if ($model instanceof \App\Models\tasks\Tareas) {
                Activity::log(
                    $user->id,
                    $model->id,
                    'created',
                    'creó la tarea',
                    [
                        'tarea_title' => $model->name ?? $model->title ?? 'Sin título',
                        'status' => $model->status,
                    ]
                );
            }
        });

        // Al actualizar una tarea
        static::updated(function ($model) {
            $user = Auth::user();
            if (!$user) return;

            // Si el modelo ES una tarea
            if ($model instanceof \App\Models\tasks\Tareas) {
                $changes = $model->getDirty();
                $original = $model->getOriginal();

                // Detectar cambios importantes
                $activityType = 'updated';
                $description = 'actualizó la tarea';
                $metadata = [];

                // Cambio de estado
                if (isset($changes['status'])) {
                    $activityType = 'status_change';
                    $description = "cambió el estado de '{$original['status']}' a '{$changes['status']}'";
                    $metadata = [
                        'old_status' => $original['status'],
                        'new_status' => $changes['status'],
                    ];
                }
                // Cambio de fecha de vencimiento
                elseif (isset($changes['due_date'])) {
                    $activityType = 'due_date';
                    $description = 'actualizó la fecha de vencimiento';
                    $metadata = [
                        'old_due_date' => $original['due_date'],
                        'new_due_date' => $changes['due_date'],
                    ];
                }
                // Tarea completada
                elseif (isset($changes['status']) && $changes['status'] === 'completed') {
                    $activityType = 'completed';
                    $description = 'completó la tarea';
                }

                Activity::log(
                    $user->id,
                    $model->id,
                    $activityType,
                    $description,
                    array_merge($metadata, [
                        'tarea_title' => $model->name ?? $model->title ?? 'Sin título',
                    ])
                );
            }
        });

        // Al eliminar una tarea (soft delete)
        static::deleted(function ($model) {
            $user = Auth::user();
            if (!$user) return;

            // Si el modelo ES una tarea
            if ($model instanceof \App\Models\tasks\Tareas) {
                Activity::log(
                    $user->id,
                    $model->id,
                    'deleted',
                    'eliminó la tarea',
                    [
                        'tarea_title' => $model->name ?? $model->title ?? 'Sin título',
                    ]
                );
            }
        });
    }

    /**
     * Registrar actividad de asignación de miembro
     * 
     * @param int $userId - ID del usuario asignado
     * @param string $userName - Nombre del usuario asignado
     * @return void
     */
    public function logMemberAssignment($userId, $userName)
    {
        $currentUser = Auth::user();
        if (!$currentUser) return;

        Activity::log(
            $currentUser->id,
            $this->id,
            'member_added',
            "asignó a {$userName} a la tarea",
            [
                'assigned_user_id' => $userId,
                'assigned_user_name' => $userName,
                'tarea_title' => $this->name ?? $this->title ?? 'Sin título',
            ]
        );
    }

    /**
     * Registrar actividad de remoción de miembro
     * 
     * @param int $userId - ID del usuario removido
     * @param string $userName - Nombre del usuario removido
     * @return void
     */
    public function logMemberRemoval($userId, $userName)
    {
        $currentUser = Auth::user();
        if (!$currentUser) return;

        Activity::log(
            $currentUser->id,
            $this->id,
            'member_removed',
            "removió a {$userName} de la tarea",
            [
                'removed_user_id' => $userId,
                'removed_user_name' => $userName,
                'tarea_title' => $this->name ?? $this->title ?? 'Sin título',
            ]
        );
    }

    /**
     * Registrar actividad de adjunto
     * 
     * @param string $fileName - Nombre del archivo
     * @return void
     */
    public function logAttachment($fileName)
    {
        $user = Auth::user();
        if (!$user) return;

        Activity::log(
            $user->id,
            $this->id,
            'attachment',
            "agregó el archivo '{$fileName}'",
            [
                'file_name' => $fileName,
                'tarea_title' => $this->name ?? $this->title ?? 'Sin título',
            ]
        );
    }

    /**
     * Registrar actividad de checklist
     * 
     * @param string $checklistTitle - Título del checklist
     * @param string $action - Acción realizada (added, updated, removed)
     * @return void
     */
    public function logChecklist($checklistTitle, $action = 'added')
    {
        $user = Auth::user();
        if (!$user) return;

        $descriptions = [
            'added' => "agregó el checklist '{$checklistTitle}'",
            'updated' => "actualizó el checklist '{$checklistTitle}'",
            'removed' => "eliminó el checklist '{$checklistTitle}'",
        ];

        Activity::log(
            $user->id,
            $this->id,
            'checklist',
            $descriptions[$action] ?? "modificó el checklist '{$checklistTitle}'",
            [
                'checklist_title' => $checklistTitle,
                'action' => $action,
                'tarea_title' => $this->name ?? $this->title ?? 'Sin título',
            ]
        );
    }

    /**
     * Registrar actividad personalizada
     * 
     * @param string $type - Tipo de actividad
     * @param string $description - Descripción
     * @param array $metadata - Datos adicionales
     * @return void
     */
    public function logCustomActivity($type, $description, $metadata = [])
    {
        $user = Auth::user();
        if (!$user) return;

        Activity::log(
            $user->id,
            $this->id,
            $type,
            $description,
            array_merge($metadata, [
                'tarea_title' => $this->name ?? $this->title ?? 'Sin título',
            ])
        );
    }
}