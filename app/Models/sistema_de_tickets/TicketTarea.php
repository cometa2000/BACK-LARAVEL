<?php

namespace App\Models\sistema_de_tickets;

use App\Models\tasks\Tareas;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketTarea extends Model
{
    use HasFactory;

    protected $table = 'ticket_tareas';

    protected $fillable = [
        'ticket_id',
        'tarea_id',
        'ticket_message_id',
        'user_id',
    ];

    // ================================================================
    // RELACIONES
    // ================================================================

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function tarea()
    {
        return $this->belongsTo(Tareas::class, 'tarea_id');
    }

    public function message()
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}