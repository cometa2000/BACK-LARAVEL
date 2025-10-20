<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class GrupoCreadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $grupoNombre;
    public $usuarioNombre;
    public $grupoUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($grupoNombre, $usuarioNombre, $grupoId)
    {
        $this->grupoNombre = $grupoNombre;
        $this->usuarioNombre = $usuarioNombre;
        $this->grupoUrl = env('FRONTEND_URL') . '/tasks/tareas/tablero/' . $grupoId;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME')),
            subject: '🎉 Grupo Creado: ' . $this->grupoNombre,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.grupo-creado',
            with: [
                'grupoNombre' => $this->grupoNombre,
                'usuarioNombre' => $this->usuarioNombre,
                'grupoUrl' => $this->grupoUrl,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}