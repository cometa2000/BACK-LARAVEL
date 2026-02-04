<?php

namespace App\Models\tasks;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Workspace extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'color',
        'user_id',
    ];

    protected $appends = ['grupos_count'];

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
     * 👤 Relación con el usuario propietario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 📁 Relación con los grupos del workspace
     */
    public function grupos()
    {
        return $this->hasMany(Grupos::class, 'workspace_id');
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * 📊 Contador de grupos
     */
    public function getGruposCountAttribute()
    {
        return $this->grupos()->count();
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * 🔍 Scope: Obtener workspaces del usuario autenticado
     */
    public function scopeOfUser($query, $userId)
    {
        return $query->where('user_id', $userId);
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
    // MÉTODOS AUXILIARES
    // ========================================

    /**
     * ✅ Verificar si el usuario es propietario del workspace
     */
    public function isOwner($userId)
    {
        return $this->user_id == $userId;
    }

    /**
     * 📊 Obtener estadísticas del workspace
     */
    public function getStats()
    {
        $gruposCount = $this->grupos()->count();
        $tareasCount = \App\Models\tasks\Tareas::whereHas('grupo', function($query) {
            $query->where('workspace_id', $this->id);
        })->count();

        return [
            'grupos_count' => $gruposCount,
            'tareas_count' => $tareasCount,
        ];
    }
}