<?php

namespace App\Models\sistema_de_tickets;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketAssignment extends Model
{
    use HasFactory;

    protected $table = 'ticket_assignments';

    protected $fillable = [
        'ticket_id',
        'asignado_por_id',
        'asignado_a_id',
        'motivo',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function asignadoPor()
    {
        return $this->belongsTo(User::class, 'asignado_por_id');
    }

    public function asignadoA()
    {
        return $this->belongsTo(User::class, 'asignado_a_id');
    }
}