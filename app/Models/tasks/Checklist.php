<?php

namespace App\Models\tasks;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Checklist extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'tarea_id',
        'orden'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación con Tareas
    public function tarea()
    {
        return $this->belongsTo(Tareas::class, 'tarea_id');
    }

    // Relación con Items
    public function items()
    {
        return $this->hasMany(ChecklistItem::class, 'checklist_id')->orderBy('orden');
    }

    // Calcular progreso
    public function getProgressAttribute()
    {
        $total = $this->items()->count();
        if ($total === 0) return 0;
        
        $completed = $this->items()->where('completed', true)->count();
        return round(($completed / $total) * 100);
    }
}