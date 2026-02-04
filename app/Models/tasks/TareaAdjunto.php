<?php

namespace App\Models\tasks;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TareaAdjunto extends Model
{
    use HasFactory;

    protected $table = 'tarea_adjuntos';

    protected $fillable = [
        'tarea_id',
        'tipo',
        'nombre',
        'url',
        'file_path',
        'mime_type',
        'size',
        'preview'
    ];

    public function tarea()
    {
        return $this->belongsTo(Tareas::class, 'tarea_id');
    }
}