<?php

namespace App\Models\documents;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Configuration\Sucursale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Documentos extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        "name",        // Nombre de documento
        "type",        // Saber si es archivo o carpeta
        
        "parent_id",    // Relación recursiva para carpetas
        "sucursale_id",  // A qué sucursal pertenece
        "user_id",      // Quién lo subió o creó

        "file_path",   // Ruta física o almacenamiento (solo si es 'file')
        "mime_type",   // Tipo de archivo (PDF, JPG, etc.)
        "size",        // Tamaño en bytes del archivo
        "description",  // Descripción opcional
    ];

    public function setCreatedAtAttribute($value) {
        date_default_timezone_set('America/Mexico_City');
        $this->attributes["created_at"] = Carbon::now();
    }
    public function setUpdatedAtAttribute($value) {
        date_default_timezone_set('America/Mexico_City');
        $this->attributes["updated_at"] = Carbon::now();
    }
    public function sucursale(){
        return $this->belongsTo(Sucursale::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
}
