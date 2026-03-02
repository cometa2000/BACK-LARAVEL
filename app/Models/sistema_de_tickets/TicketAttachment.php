<?php

namespace App\Models\sistema_de_tickets;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketAttachment extends Model
{
    use HasFactory;

    protected $table = 'ticket_attachments';

    protected $fillable = [
        'ticket_id',
        'ticket_message_id',
        'user_id',
        'nombre',
        'file_path',
        'mime_type',
        'tamanio',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function message()
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * URL pública del archivo
     */
    public function getFileUrlAttribute(): string
    {
        return url('storage/' . $this->file_path);
    }
}