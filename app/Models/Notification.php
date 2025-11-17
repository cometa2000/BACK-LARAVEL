<?php

namespace App\Models;

use App\Models\User;
use App\Models\tasks\Grupos;
use App\Models\tasks\Tareas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'from_user_id',
        'tarea_id',
        'grupo_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el usuario que recibe la notificación
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el usuario que generó la notificación
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Relación con la tarea
     */
    public function tarea()
    {
        return $this->belongsTo(Tareas::class);
    }

    /**
     * Relación con el grupo
     */
    public function grupo()
    {
        return $this->belongsTo(Grupos::class);
    }

    /**
     * Scope para obtener notificaciones no leídas
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope para obtener notificaciones leídas
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope para obtener notificaciones recientes
     */
    public function scopeRecent($query, $limit = 20)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Marcar como leída
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Marcar como no leída
     */
    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Obtener el ícono según el tipo de notificación
     */
    public function getIconAttribute()
    {
        $icons = [
            'task_assigned' => 'profile-user',
            'task_completed' => 'verify',
            'comment' => 'message-text-2',
            'mention' => 'notification-status',
            'due_date_reminder' => 'calendar',
            'permission_changed' => 'security-user',
            'attachment' => 'paperclip',
        ];

        return $icons[$this->type] ?? 'information';
    }

    /**
     * Obtener el color según el tipo de notificación
     */
    public function getColorAttribute()
    {
        $colors = [
            'task_assigned' => 'success',
            'task_completed' => 'success',
            'comment' => 'primary',
            'mention' => 'warning',
            'due_date_reminder' => 'danger',
            'permission_changed' => 'info',
            'attachment' => 'warning',
        ];

        return $colors[$this->type] ?? 'secondary';
    }

    /**
     * Método estático para crear una notificación
     */
    public static function notify($userId, $type, $title, $message, $data = [])
    {
        return self::create([
            'user_id' => $userId,
            'from_user_id' => $data['from_user_id'] ?? null,
            'tarea_id' => $data['tarea_id'] ?? null,
            'grupo_id' => $data['grupo_id'] ?? null,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
