<?php

namespace App\Models\tasks;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Configuration\Sucursale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tareas extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = 'tareas';
    protected $fillable = [  
        "name",
        "description",
        "type_task",
        "priority",
        "start_date",
        "due_date",
        "status",
        "sucursale_id",
        "user_id",
        "grupo_id",
        "lista_id",
        "estimated_time",
        "file_path",
        "budget",
        "address",
        "attendees",
        "subtasks",
    ];

    public function setCreatedAtAttribute($value) {
        date_default_timezone_set("America/Mexico_City");
        $this->attributes["created_at"] = Carbon::now();
    }
    
    public function setUpdatedAtAttribute($value) {
        date_default_timezone_set("America/Mexico_City");
        $this->attributes["updated_at"] = Carbon::now();
    }

    // Relaciones existentes
    public function sucursale(){
        return $this->belongsTo(Sucursale::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function grupo(){
        return $this->belongsTo(Grupos::class);
    }

    public function lista() {
        return $this->belongsTo(Lista::class);
    }

    // 🆕 Nuevas relaciones
    public function comentarios() {
        return $this->hasMany(Comentario::class, 'tarea_id')->orderBy('created_at', 'desc');
    }

    public function actividades() {
        return $this->hasMany(Actividad::class, 'tarea_id')->orderBy('created_at', 'desc');
    }
}