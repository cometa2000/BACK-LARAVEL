<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Modelo extendido de Role de Spatie para soportar SoftDeletes
 * 
 * Este modelo extiende el Role de Spatie/Permission para agregar
 * funcionalidad de soft deletes (borrado lógico)
 */
class Role extends SpatieRole
{
    use SoftDeletes;

    /**
     * Los atributos que deben ser convertidos a fechas
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Relación con usuarios que tienen este rol
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function user()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    /**
     * Verifica si el rol tiene usuarios asignados
     * 
     * @return bool
     */
    public function hasUsers()
    {
        return $this->users()->exists();
    }

    /**
     * Obtiene el número de usuarios con este rol
     * 
     * @return int
     */
    public function getUsersCount()
    {
        return $this->users()->count();
    }
}