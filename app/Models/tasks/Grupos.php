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
        "is_starred"
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

    // Relación con listas
    public function listas()
    {
        return $this->hasMany(Lista::class, 'grupo_id');
    }

    // Relación con el propietario
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación many-to-many con usuarios compartidos
    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'grupo_user', 'grupo_id', 'user_id')
                    ->withTimestamps();
    }

    // Scope para filtrar grupos accesibles por un usuario
    public function scopeAccessibleBy($query, $userId)
    {
        return $query->where('user_id', $userId)
                     ->orWhereHas('sharedUsers', function($q) use ($userId) {
                         $q->where('users.id', $userId);
                     });
    }
}

