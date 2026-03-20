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
        'documento_id',      // FK opcional al sistema de documentos
        'es_url_externa',    // true cuando file_path contiene una URL externa
        'nombre',
        'file_path',
        'mime_type',
        'tamanio',
    ];

    protected $casts = [
        'es_url_externa' => 'boolean',
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
     * URL pública del archivo.
     * - Si es URL externa: file_path ya contiene la URL completa.
     * - Si es archivo físico: construye la URL de storage.
     */
    public function getFileUrlAttribute(): string
    {
        if ($this->es_url_externa) {
            return $this->file_path;
        }
        return url('storage/' . $this->file_path);
    }
}