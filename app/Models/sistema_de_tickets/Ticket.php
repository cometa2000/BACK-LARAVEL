<?php

namespace App\Models\sistema_de_tickets;

use App\Models\User;
use App\Models\Configuration\Sucursale;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\sistema_de_tickets\TicketFavorito;
use App\Models\sistema_de_tickets\TicketArchivado;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tickets';

    protected $fillable = [
        'folio',
        'creador_id',
        'asignado_id',
        'sucursale_id',
        'tipo_origen',
        'tipo_destino',
        'rol_destino_id',
        'sucursal_destino_id',
        'asunto',
        'descripcion',
        'categoria',
        'prioridad',
        'estado',
        'fecha_limite',
        'fecha_primera_respuesta',
        'fecha_en_proceso',
        'fecha_resolucion',
        'fecha_cierre',
        'es_favorito',
        'archivado',
    ];

    protected $casts = [
        'fecha_limite'             => 'date',
        'fecha_primera_respuesta'  => 'datetime',
        'fecha_en_proceso'         => 'datetime',
        'fecha_resolucion'         => 'datetime',
        'fecha_cierre'             => 'datetime',
        'es_favorito'              => 'boolean',
        'archivado'                => 'boolean',
        'created_at'               => 'datetime',
        'updated_at'               => 'datetime',
    ];

    // ================================================================
    // RELACIONES
    // ================================================================

    public function creador()
    {
        return $this->belongsTo(User::class, 'creador_id');
    }

    public function asignado()
    {
        return $this->belongsTo(User::class, 'asignado_id');
    }

    public function sucursale()
    {
        return $this->belongsTo(Sucursale::class, 'sucursale_id');
    }

    public function sucursalDestino()
    {
        return $this->belongsTo(Sucursale::class, 'sucursal_destino_id');
    }

    public function rolDestino()
    {
        return $this->belongsTo(Role::class, 'rol_destino_id');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id')
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'asc');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_id')
                    ->whereNull('ticket_message_id'); // Solo adjuntos del ticket principal
    }

    public function statusHistory()
    {
        return $this->hasMany(TicketStatusHistory::class, 'ticket_id')
                    ->orderBy('created_at', 'asc');
    }

    public function assignments()
    {
        return $this->hasMany(TicketAssignment::class, 'ticket_id')
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Usuarios que marcaron este ticket como favorito (individual).
     * Tabla pivot: ticket_favoritos
     */
    public function favoritosPor()
    {
        return $this->hasMany(TicketFavorito::class, 'ticket_id');
    }

    /**
     * Usuarios que archivaron este ticket (individual).
     * Tabla pivot: ticket_archivados
     */
    public function archivadosPor()
    {
        return $this->hasMany(TicketArchivado::class, 'ticket_id');
    }

    // ================================================================
    // MÉTODOS AUXILIARES
    // ================================================================

    /**
     * Generar folio único: TK-2025-0001
     */
    public static function generarFolio(): string
    {
        $year = date('Y');
        $prefix = "TK-{$year}-";

        $ultimo = self::withTrashed()
            ->where('folio', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimo) {
            $numero = (int) substr($ultimo->folio, strlen($prefix)) + 1;
        } else {
            $numero = 1;
        }

        return $prefix . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Verificar si el ticket está vencido
     */
    public function isVencido(): bool
    {
        if (!$this->fecha_limite) return false;
        return $this->fecha_limite->isPast() && !in_array($this->estado, ['cerrado', 'resuelto', 'rechazado']);
    }

    /**
     * Calcular tiempo de primera respuesta en minutos
     */
    public function getTiempoPrimeraRespuesta(): ?int
    {
        if (!$this->fecha_primera_respuesta) return null;
        return $this->created_at->diffInMinutes($this->fecha_primera_respuesta);
    }

    /**
     * Calcular tiempo de resolución en horas
     */
    public function getTiempoResolucion(): ?float
    {
        if (!$this->fecha_resolucion) return null;
        return round($this->created_at->diffInMinutes($this->fecha_resolucion) / 60, 1);
    }

    /**
     * Tareas adjuntas al ticket principal (ticket_message_id IS NULL)
     */
    public function tareas()
    {
        return $this->hasMany(TicketTarea::class, 'ticket_id')
                    ->whereNull('ticket_message_id')
                    ->with('tarea:id,name,status,priority,due_date,grupo_id,lista_id');
    }

    /**
     * Todas las tareas adjuntas (principal + hilo de mensajes)
     */
    public function todasLasTareas()
    {
        return $this->hasMany(TicketTarea::class, 'ticket_id')
                    ->with('tarea:id,name,status,priority,due_date,grupo_id,lista_id');
    }
}