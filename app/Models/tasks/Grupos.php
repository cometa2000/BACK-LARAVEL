<?php

namespace App\Models\tasks;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Grupos extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'grupos';
    
    protected $fillable = [  
        "name",
        "color",
        "image",
        "user_id",
        "workspace_id",  // ✅ CAMPO WORKSPACE
        "is_starred",
        "permission_type"
    ];

    protected $casts = [
        'is_starred' => 'boolean',
    ];

    // ========================================
    // TIMEZONE MANAGEMENT
    // ========================================
    public function setCreatedAtAttribute($value)
    {
        date_default_timezone_set("America/Mexico_City");
        $this->attributes["created_at"] = Carbon::now();
    }
    
    public function setUpdatedAtAttribute($value)
    {
        date_default_timezone_set("America/Mexico_City");
        $this->attributes["updated_at"] = Carbon::now();
    }

    // ========================================
    // RELACIONES
    // ========================================

    /**
     * 🏢 Relación con workspace
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    /**
     * 📋 Relación con listas
     */
    public function listas()
    {
        return $this->hasMany(Lista::class, 'grupo_id');
    }

    /**
     * ✅ Relación con tareas (a través de listas)
     */
    public function tareas()
    {
        return $this->hasManyThrough(
            Tareas::class,
            Lista::class,
            'grupo_id',  // Foreign key en tabla listas
            'lista_id',  // Foreign key en tabla tareas
            'id',        // Local key en tabla grupos
            'id'         // Local key en tabla listas
        );
    }

    /**
     * 👤 Relación con el usuario creador del grupo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 👤 Alias para compatibilidad
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 👥 Relación many-to-many con usuarios compartidos
     */
    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'grupo_user', 'grupo_id', 'user_id')
                    ->withPivot('permission_level')
                    ->withTimestamps();
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * 🔍 Scope: Grupos accesibles por un usuario (propios + compartidos)
     */
    public function scopeAccessibleBy($query, $userId)
    {
        return $query->where('user_id', $userId)
                     ->orWhereHas('sharedUsers', function($q) use ($userId) {
                         $q->where('users.id', $userId);
                     });
    }

    /**
     * 🔍 Scope: Solo grupos del usuario (propios)
     */
    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 🔍 Scope: Solo grupos compartidos con el usuario
     */
    public function scopeSharedWith($query, $userId)
    {
        return $query->whereHas('sharedUsers', function($q) use ($userId) {
            $q->where('users.id', $userId);
        });
    }

    /**
     * 🔍 Scope: Grupos de un workspace específico
     */
    public function scopeOfWorkspace($query, $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * ⭐ Scope: Solo grupos marcados como favoritos
     */
    public function scopeStarred($query)
    {
        return $query->where('is_starred', true);
    }

    /**
     * 🔍 Scope: Buscar por nombre
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('name', 'like', '%' . $search . '%');
        }
        return $query;
    }

    // ========================================
    // MÉTODOS DE PERMISOS
    // ========================================

    /**
     * ✅ Verificar si un usuario es el propietario del grupo
     */
    public function isOwner($userId)
    {
        return $this->user_id == $userId;
    }

    /**
     * ✍️ Verificar si un usuario tiene acceso de escritura (puede crear, editar, eliminar)
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
     * 👁️ Verificar si un usuario tiene al menos acceso de lectura
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
     * 📊 Obtener el nivel de permiso de un usuario específico
     * Retorna: 'owner', 'write', 'read', 'none'
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

    /**
     * 🔐 Verificar si un usuario puede gestionar permisos del grupo
     * Solo el propietario puede gestionar permisos
     */
    public function canManagePermissions($userId)
    {
        return $this->isOwner($userId);
    }

    /**
     * 🗑️ Verificar si un usuario puede eliminar el grupo
     * Solo el propietario puede eliminar
     */
    public function canDelete($userId)
    {
        return $this->isOwner($userId);
    }

    // ========================================
    // MÉTODOS AUXILIARES
    // ========================================

    /**
     * 📊 Obtener estadísticas del grupo
     */
    public function getStats()
    {
        $listasCount = $this->listas()->count();
        $tareasCount = $this->tareas()->count();
        $tareasCompletadas = $this->tareas()->where('status', 'completada')->count();

        return [
            'listas_count' => $listasCount,
            'tareas_count' => $tareasCount,
            'tareas_completadas' => $tareasCompletadas,
            'tareas_pendientes' => $tareasCount - $tareasCompletadas,
        ];
    }

    /**
     * 👥 Obtener todos los usuarios con acceso al grupo
     */
    public function getAllUsers()
    {
        $owner = $this->user;
        $sharedUsers = $this->sharedUsers;

        return collect([$owner])->merge($sharedUsers);
    }

    /**
     * 🎨 Obtener URL completa de la imagen
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        return url('storage/' . $this->image);
    }
}