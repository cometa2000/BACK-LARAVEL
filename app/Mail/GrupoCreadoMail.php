<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class GrupoCreadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $nombreGrupo;
    public $nombreUsuario;

    /**
     * Create a new message instance.
     */
    public function __construct($nombreGrupo, $nombreUsuario)
    {
        $this->nombreGrupo = $nombreGrupo;
        $this->nombreUsuario = $nombreUsuario;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: '✅ Grupo Creado Exitosamente - ' . $this->nombreGrupo,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.grupo-creado',
            with: [
                'nombreGrupo' => $this->nombreGrupo,
                'nombreUsuario' => $this->nombreUsuario,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}