<?php

namespace App\Traits;

use App\Models\Activity;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

trait ActivityLogger
{
    /**
     * Registrar actividad de creación de tarea
     */
    public function logTareaCreated($tarea)
    {
        $user = Auth::user();
        
        Activity::log(
            $user->id,
            $tarea->id,
            'created',
            'creó esta tarea',
            ['title' => $tarea->title]
        );

        // Notificar a los miembros asignados (si hay)
        if ($tarea->miembros && count($tarea->miembros) > 0) {
            foreach ($tarea->miembros as $miembro) {
                if ($miembro->id !== $user->id) {
                    Notification::notify(
                        $miembro->id,
                        'task_assigned',
                        'Nueva tarea asignada',
                        "{$user->name} te asignó la tarea: {$tarea->title}",
                        [
                            'from_user_id' => $user->id,
                            'tarea_id' => $tarea->id,
                            'grupo_id' => $tarea->lista->grupo_id ?? null,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Registrar actividad de completar tarea
     */
    public function logTareaCompleted($tarea)
    {
        $user = Auth::user();
        
        Activity::log(
            $user->id,
            $tarea->id,
            'completed',
            'completó esta tarea',
            ['title' => $tarea->title]
        );

        // Notificar al creador (si no es el mismo usuario)
        if ($tarea->user_id && $tarea->user_id !== $user->id) {
            Notification::notify(
                $tarea->user_id,
                'task_completed',
                'Tarea completada',
                "{$user->name} completó la tarea: {$tarea->title}",
                [
                    'from_user_id' => $user->id,
                    'tarea_id' => $tarea->id,
                    'grupo_id' => $tarea->lista->grupo_id ?? null,
                ]
            );
        }
    }

    /**
     * Registrar actividad de cambio de estado
     */
    public function logTareaStatusChanged($tarea, $oldStatus, $newStatus)
    {
        $user = Auth::user();
        
        $statusLabels = [
            'pending' => 'Pendiente',
            'in_progress' => 'En Progreso',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
        ];
        
        Activity::log(
            $user->id,
            $tarea->id,
            'status_change',
            "cambió el estado de {$statusLabels[$oldStatus]} a {$statusLabels[$newStatus]}",
            [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]
        );
    }

    /**
     * Registrar actividad de asignación de miembros
     */
    public function logMemberAssigned($tarea, $miembro)
    {
        $user = Auth::user();
        
        Activity::log(
            $user->id,
            $tarea->id,
            'assignment',
            "asignó a {$miembro->name} {$miembro->surname}",
            ['member_id' => $miembro->id]
        );

        // Notificar al miembro asignado
        if ($miembro->id !== $user->id) {
            Notification::notify(
                $miembro->id,
                'task_assigned',
                'Te asignaron a una tarea',
                "{$user->name} te asignó a la tarea: {$tarea->title}",
                [
                    'from_user_id' => $user->id,
                    'tarea_id' => $tarea->id,
                    'grupo_id' => $tarea->lista->grupo_id ?? null,
                ]
            );
        }
    }

    /**
     * Registrar actividad de cambio de fecha de vencimiento
     */
    public function logDueDateChanged($tarea, $oldDate, $newDate)
    {
        $user = Auth::user();
        
        Activity::log(
            $user->id,
            $tarea->id,
            'due_date',
            "cambió la fecha de vencimiento",
            [
                'old_date' => $oldDate,
                'new_date' => $newDate,
            ]
        );
    }

    /**
     * Registrar actividad de adjunto agregado
     */
    public function logAttachmentAdded($tarea, $fileName)
    {
        $user = Auth::user();
        
        Activity::log(
            $user->id,
            $tarea->id,
            'attachment',
            "adjuntó un archivo: {$fileName}",
            ['file_name' => $fileName]
        );
    }

    /**
     * Registrar actividad de comentario
     */
    public function logComment($tarea, $comment)
    {
        $user = Auth::user();
        
        return Activity::log(
            $user->id,
            $tarea->id,
            'comment',
            $comment,
            []
        );
    }

    /**
     * Registrar actividad de checklist actualizado
     */
    public function logChecklistUpdated($tarea, $checklistItem, $isCompleted)
    {
        $user = Auth::user();
        
        $action = $isCompleted ? 'completó' : 'marcó como incompleto';
        
        Activity::log(
            $user->id,
            $tarea->id,
            'checklist',
            "{$action} el ítem: {$checklistItem}",
            [
                'checklist_item' => $checklistItem,
                'is_completed' => $isCompleted,
            ]
        );
    }
}
