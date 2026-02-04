<?php

namespace App\Models\tasks;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Timeline extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'timeline';

    protected $fillable = [
        'tarea_id',
        'user_id',
        'action',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========== RELACIONES ==========

    /**
     * Relación con la tarea
     */
    public function tarea()
    {
        return $this->belongsTo(Tareas::class, 'tarea_id');
    }

    /**
     * Relación con el usuario que realizó la acción
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}