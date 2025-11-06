<?php

namespace App\Models\tasks;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Grupos extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = 'grupos';
    
    protected $fillable = [  
        "name",
        "color",
        "image",
        "user_id",
        "is_starred",
        "permission_type"
    ];

    protected $casts = [
        'is_starred' => 'boolean',
    ];

    public function setCreatedAtAttribute($value) {
        date_default_timezone_set("America/Mexico_City");
        $this->attributes["created_at"] = Carbon::now();
    }
    
    public function setUpdatedAtAttribute($value) {
        date_default_timezone_set("America/Mexico_City");
        $this->attributes["updated_at"] = Carbon::now();
    }

    // ✅ Relación con listas
    public function listas()
    {
        return $this->hasMany(Lista::class, 'grupo_id');
    }

    // ✅ CRÍTICO: Relación con el usuario que creó el grupo
    // Esta es la relación que estaba faltando y causaba el error
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ✅ Alias para compatibilidad (apunta a la misma relación)
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ✅ Relación many-to-many con usuarios compartidos
    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'grupo_user', 'grupo_id', 'user_id')
                    ->withPivot('permission_level')
                    ->withTimestamps();
    }

    // ✅ Scope para filtrar grupos accesibles por un usuario
    public function scopeAccessibleBy($query, $userId)
    {
        return $query->where('user_id', $userId)
                     ->orWhereHas('sharedUsers', function($q) use ($userId) {
                         $q->where('users.id', $userId);
                     });
    }

    // ========================================
    // MÉTODOS DE PERMISOS
    // ========================================

    /**
     * Verificar si un usuario es el propietario del grupo
     */
    public function isOwner($userId)
    {
        return $this->user_id == $userId;
    }

    /**
     * Verificar si un usuario tiene acceso de escritura (puede crear, editar, eliminar)
     */
    public function hasWriteAccess($userId)
    {
        // El propietario siempre tiene acceso completo
        if ($this->isOwner($userId)) {
            return true;
        }

        // Si el tipo de permiso es 'all', todos tienen acceso completo
        if ($this->permission_type === 'all') {
            return true;
        }

        // Si el tipo de permiso es 'readonly', nadie más tiene acceso de escritura
        if ($this->permission_type === 'readonly') {
            return false;
        }

        // Si es 'custom', verificar el nivel de permiso específico del usuario
        if ($this->permission_type === 'custom') {
            $pivot = $this->sharedUsers()->where('users.id', $userId)->first();
            if ($pivot) {
                return $pivot->pivot->permission_level === 'write';
            }
            return false;
        }

        return false;
    }

    /**
     * Verificar si un usuario tiene al menos acceso de lectura
     */
    public function hasReadAccess($userId)
    {
        // El propietario siempre tiene acceso
        if ($this->isOwner($userId)) {
            return true;
        }

        // Si está en la lista de usuarios compartidos, tiene al menos lectura
        return $this->sharedUsers()->where('users.id', $userId)->exists();
    }

    /**
     * Obtener el nivel de permiso de un usuario específico
     */
    public function getUserPermissionLevel($userId)
    {
        // El propietario tiene permisos completos
        if ($this->isOwner($userId)) {
            return 'owner';
        }

        // Si no es un usuario compartido, no tiene acceso
        $pivot = $this->sharedUsers()->where('users.id', $userId)->first();
        if (!$pivot) {
            return 'none';
        }

        // Retornar según el tipo de permiso del grupo
        if ($this->permission_type === 'all') {
            return 'write';
        }

        if ($this->permission_type === 'readonly') {
            return 'read';
        }

        // Si es custom, retornar el nivel específico
        return $pivot->pivot->permission_level;
    }
}