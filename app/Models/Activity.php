<?php

namespace App\Models;

use App\Models\User;
use App\Models\tasks\Tareas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tarea_id',
        'type',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el usuario que realizó la actividad
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con la tarea
     * ⚠️ IMPORTANTE: Debe ser 'tarea' (singular) no 'tareas'
     */
    public function tarea()
    {
        return $this->belongsTo(Tareas::class, 'tarea_id');
    }

    /**
     * ❌ ELIMINAR: Esta relación no debería existir
     * Activity no tiene grupo_id, se relaciona con Grupo a través de Tarea
     */
    // public function grupos()
    // {
    //     return $this->belongsTo(Grupos::class);
    // }

    /**
     * Scope para obtener actividades recientes
     */
    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope para obtener actividades de una tarea específica
     */
    public function scopeByTarea($query, $tareaId)
    {
        return $query->where('tarea_id', $tareaId);
    }

    /**
     * Scope para obtener actividades de un usuario específico
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Obtener el ícono según el tipo de actividad
     */
    public function getIconAttribute()
    {
        $icons = [
            'comment' => 'message-text-2',
            'status_change' => 'setting-3',
            'assignment' => 'profile-user',
            'attachment' => 'paperclip',
            'due_date' => 'calendar',
            'checklist' => 'check-square',
            'created' => 'plus-square',
            'completed' => 'verify',
            'deleted' => 'trash',
            'updated' => 'pencil',
            'member_added' => 'profile-user',
            'member_removed' => 'profile-delete',
        ];

        return $icons[$this->type] ?? 'information';
    }

    /**
     * Obtener el color según el tipo de actividad
     */
    public function getColorAttribute()
    {
        $colors = [
            'comment' => 'primary',
            'status_change' => 'info',
            'assignment' => 'success',
            'attachment' => 'warning',
            'due_date' => 'danger',
            'checklist' => 'primary',
            'created' => 'success',
            'completed' => 'success',
            'deleted' => 'danger',
            'updated' => 'info',
            'member_added' => 'success',
            'member_removed' => 'warning',
        ];

        return $colors[$this->type] ?? 'secondary';
    }

    /**
     * Método estático para registrar una actividad
     * 
     * @param int $userId - ID del usuario que realiza la acción
     * @param int $tareaId - ID de la tarea relacionada
     * @param string $type - Tipo de actividad
     * @param string $description - Descripción de la actividad
     * @param array $metadata - Datos adicionales
     * @return Activity
     */
    public static function log($userId, $tareaId, $type, $description, $metadata = [])
    {
        try {
            return self::create([
                'user_id' => $userId,
                'tarea_id' => $tareaId,
                'type' => $type,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al registrar actividad: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Registrar múltiples actividades de una vez
     * 
     * @param array $activities - Array de actividades
     * @return bool
     */
    public static function logBatch($activities)
    {
        try {
            foreach ($activities as $activity) {
                self::log(
                    $activity['user_id'],
                    $activity['tarea_id'],
                    $activity['type'],
                    $activity['description'],
                    $activity['metadata'] ?? []
                );
            }
            return true;
        } catch (\Exception $e) {
            \Log::error('Error al registrar actividades en batch: ' . $e->getMessage());
            return false;
        }
    }
}