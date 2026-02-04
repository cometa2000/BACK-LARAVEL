<?php

namespace App\Models\tasks;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comentario extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'comentarios';
    
    protected $fillable = [
        'content',
        'tarea_id',
        'user_id',
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