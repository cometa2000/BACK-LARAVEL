<?php

namespace App\Models\tasks;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Actividad extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'actividades';
    
    protected $fillable = [
        'type',
        'description',
        'changes',
        'tarea_id',
        'user_id',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function setCreatedAtAttribute($value) {
        date_default_timezone_set("America/Mexico_City");
        $this->attributes["created_at"] = Carbon::now();
    }
    
    public function setUpdatedAtAttribute($value) {
        date_default_timezone_set("America/Mexico_City");
        $this->attributes["updated_at"] = Carbon::now();
    }

    public function tarea() {
        return $this->belongsTo(Tareas::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}