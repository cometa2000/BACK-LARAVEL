<?php

namespace App\Models\sistema_de_tickets;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Áreas configurables de la sede principal.
 * Solo el Super-Admin puede crear / editar / eliminar registros.
 * Un área tiene exactamente un responsable (usuario de hueso).
 * Varias áreas pueden tener distinto nombre pero el mismo responsable,
 * o el mismo nombre con diferentes responsables (flexibilidad total).
 *
 * Tabla: ticket_areas
 */
class TicketArea extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ticket_areas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'responsable_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ── Relaciones ─────────────────────────────────────────────────

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}