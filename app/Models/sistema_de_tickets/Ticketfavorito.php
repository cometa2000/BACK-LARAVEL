<?php

namespace App\Models\sistema_de_tickets;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Favoritos individuales por usuario.
 * Un registro = un usuario marcó un ticket como favorito.
 * Tabla: ticket_favoritos
 */
class TicketFavorito extends Model
{
    use HasFactory;

    protected $table = 'ticket_favoritos';

    protected $fillable = [
        'ticket_id',
        'user_id',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}