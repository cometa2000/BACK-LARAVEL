<?php

namespace App\Models\tasks;

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
        'orden'
    ];

    protected $casts = [
        'completed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación con Checklist
    public function checklist()
    {
        return $this->belongsTo(Checklist::class, 'checklist_id');
    }
}