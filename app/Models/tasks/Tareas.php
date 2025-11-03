<?php

namespace App\Models\tasks;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Tareas extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type_task',
        'priority',
        'start_date',
        'due_date',
        'status',
        'sucursale_id',
        'user_id',
        'grupo_id',
        'lista_id',
        'orden',
        'estimated_time',
        'file_path',
        'budget',
        'address',
        'attendees',
        'subtasks',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'subtasks' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========== RELACIONES EXISTENTES ==========

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupos::class, 'grupo_id');
    }

    public function lista()
    {
        return $this->belongsTo(Lista::class, 'lista_id');
    }

    

    public function actividades()
    {
        return $this->hasMany(Actividad::class, 'tarea_id');
    }

    public function etiqueta(){
        return $this->belongsTo(Etiqueta::class);
    }


    public function adjuntos()
    {
        return $this->hasMany(TareaAdjunto::class, 'tarea_id');
    }

    public function etiquetas()
    {
        return $this->hasMany(Etiqueta::class, 'tarea_id')->orderBy('created_at', 'asc');
    }

    public function checklists()
    {
        return $this->hasMany(Checklist::class, 'tarea_id')->orderBy('orden', 'asc');
    }

    public function comentarios()
    {
        return $this->hasMany(Comentario::class, 'tarea_id');
    }

    

    // ========== MÉTODOS ÚTILES ==========

    /**
     * Verificar si la tarea está vencida
     */
    public function isOverdue()
    {
        if (!$this->due_date) {
            return false;
        }
        return $this->due_date->isPast();
    }

    /**
     * Verificar si la fecha de vencimiento está próxima (dentro de 3 días)
     */
    public function isDueSoon()
    {
        if (!$this->due_date) {
            return false;
        }
        $now = now();
        return $this->due_date->isFuture() && $this->due_date->diffInDays($now) <= 3;
    }

    /**
     * Obtener el progreso total de todos los checklists
     */
    public function getTotalChecklistProgress()
    {
        $checklists = $this->checklists;
        
        if ($checklists->isEmpty()) {
            return 0;
        }

        $totalProgress = $checklists->sum('progress');
        return round($totalProgress / $checklists->count());
    }

    /**
     * Contar total de items en checklists
     */
    public function getTotalChecklistItems()
    {
        return $this->checklists->sum(function($checklist) {
            return $checklist->items()->count();
        });
    }

    /**
     * Contar items completados en checklists
     */
    public function getCompletedChecklistItems()
    {
        return $this->checklists->sum(function($checklist) {
            return $checklist->items()->where('completed', true)->count();
        });
    }
}