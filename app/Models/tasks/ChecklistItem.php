<?php

namespace App\Models\tasks;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChecklistItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'completed',
        'checklist_id',
        'orden',
        'due_date'
    ];

    protected $casts = [
        'completed' => 'boolean',
        'due_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación con Checklist
    public function checklist()
    {
        return $this->belongsTo(Checklist::class, 'checklist_id');
    }

    // Relación many-to-many con Users (miembros asignados)
    public function assignedUsers()
    {
        return $this->belongsToMany(
            User::class,
            'checklist_item_user',
            'checklist_item_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * Verificar si el item está vencido
     */
    public function isOverdue()
    {
        if (!$this->due_date) {
            return false;
        }
        return $this->due_date->isPast() && !$this->completed;
    }

    /**
     * Verificar si la fecha está próxima (dentro de 3 días)
     */
    public function isDueSoon()
    {
        if (!$this->due_date) {
            return false;
        }
        $now = now();
        return $this->due_date->isFuture() && $this->due_date->diffInDays($now) <= 3 && !$this->completed;
    }
}