<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class GrupoCompartidoInvitadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $nombreGrupo;
    public $nombreInvitado;
    public $nombrePropietario;

    /**
     * Create a new message instance.
     */
    public function __construct($nombreGrupo, $nombreInvitado, $nombrePropietario)
    {
        $this->nombreGrupo = $nombreGrupo;
        $this->nombreInvitado = $nombreInvitado;
        $this->nombrePropietario = $nombrePropietario;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Has sido agregado al grupo: ' . $this->nombreGrupo,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.grupo-compartido-invitado',
            with: [
                'nombreGrupo' => $this->nombreGrupo,
                'nombreInvitado' => $this->nombreInvitado,
                'nombrePropietario' => $this->nombrePropietario,
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