<?php

namespace App\Models\sistema_de_tickets;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'ticket_status_history';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'estado_anterior',
        'estado_nuevo',
        'comentario',
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