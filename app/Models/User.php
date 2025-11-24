<?php

namespace App\Models;

// Laravel imports
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

// Spatie Permission
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

// JWT
use Tymon\JWTAuth\Contracts\JWTSubject;

// Configuración
use App\Models\Configuration\Sucursale;

// Modelos relacionados
use App\Models\tasks\Grupos;
use App\Models\tasks\Tareas;
use App\Models\documents\Documentos;
use App\Models\Notification;
use App\Models\Activity;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'surname',
        'phone',
        'role_id',
        'sucursale_id',
        'type_document',
        'n_document',
        'address',
        'gender',
        'avatar'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ========================================
    // MÉTODOS JWT (OBLIGATORIOS)
    // ========================================

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
 
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    // ========================================
    // RELACIONES BÁSICAS
    // ========================================

    /**
     * Relación con Role (Spatie)
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    
    /**
     * Relación con Sucursale
     */
    public function sucursale()
    {
        return $this->belongsTo(Sucursale::class);
    }

    // ========================================
    // RELACIONES CON GRUPOS
    // ========================================

    /**
     * Grupos creados por el usuario (como propietario)
     */
    public function ownedGrupos()
    {
        return $this->hasMany(Grupos::class, 'user_id');
    }

    /**
     * Grupos compartidos con el usuario (many-to-many)
     */
    public function sharedGrupos()
    {
        return $this->belongsToMany(Grupos::class, 'grupo_user', 'user_id', 'grupo_id')
                    ->withPivot('permission_level')
                    ->withTimestamps();
    }

    /**
     * Verificar si el usuario tiene acceso a un grupo específico
     */
    public function hasAccessToGrupo($grupoId)
    {
        return $this->ownedGrupos()->where('id', $grupoId)->exists() ||
               $this->sharedGrupos()->where('id', $grupoId)->exists();
    }

    /**
     * Verificar si el usuario es el propietario de un grupo
     */
    public function ownsGrupo($grupoId)
    {
        return $this->ownedGrupos()->where('id', $grupoId)->exists();
    }

    // ========================================
    // RELACIONES CON TAREAS
    // ========================================

    /**
     * Tareas creadas por el usuario
     */
    public function createdTareas()
    {
        return $this->hasMany(Tareas::class, 'user_id');
    }

    /**
     * Tareas asignadas al usuario (many-to-many)
     * ⭐ ESTA ES LA RELACIÓN CRÍTICA PARA EL PERFIL
     */
    public function assignedTareas()
    {
        return $this->belongsToMany(Tareas::class, 'tarea_user', 'user_id', 'tarea_id')
                    ->withTimestamps();
    }

    // ========================================
    // RELACIONES CON DOCUMENTOS
    // ========================================

    /**
     * Documentos creados por el usuario
     */
    public function documentos()
    {
        return $this->hasMany(Documentos::class, 'user_id');
    }

    // ========================================
    // RELACIONES CON NOTIFICACIONES
    // ========================================

    /**
     * Notificaciones recibidas por el usuario
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Notificaciones enviadas por el usuario (como emisor)
     */
    public function sentNotifications()
    {
        return $this->hasMany(Notification::class, 'from_user_id')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Notificaciones no leídas del usuario
     */
    public function unreadNotifications()
    {
        return $this->hasMany(Notification::class, 'user_id')
                    ->where('is_read', false)
                    ->orderBy('created_at', 'desc');
    }

    // ========================================
    // RELACIONES CON ACTIVIDADES
    // ========================================

    /**
     * Actividades creadas por el usuario
     */
    public function activities()
    {
        return $this->hasMany(Activity::class, 'user_id')
                    ->orderBy('created_at', 'desc');
    }

    // ========================================
    // MÉTODOS AUXILIARES ÚTILES
    // ========================================

    /**
     * Obtener el nombre completo del usuario
     */
    public function getFullNameAttribute()
    {
        return trim("{$this->name} {$this->surname}");
    }

    /**
     * Obtener el avatar del usuario o uno por defecto
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return env("APP_URL")."/storage/".$this->avatar;
        }
        
        // Avatar por defecto
        return 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
    }

    /**
     * Obtener el número de notificaciones no leídas
     */
    public function getUnreadNotificationsCountAttribute()
    {
        return $this->unreadNotifications()->count();
    }
}