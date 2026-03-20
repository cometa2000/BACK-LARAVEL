<?php

namespace App\Models\sistema_de_tickets;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ticket_messages';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'contenido',
        'es_nota_interna',
    ];

    protected $casts = [
        'es_nota_interna' => 'boolean',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_message_id');
    }

    /**
     * Tareas adjuntas a este mensaje específico del hilo
     */
    public function ticketTareas()
    {
        return $this->hasMany(TicketTarea::class, 'ticket_message_id')
                    ->with('tarea:id,name,status,priority,due_date,grupo_id,lista_id');
    }
}