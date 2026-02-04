<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class GrupoCompartidoPropietarioMail extends Mailable
{
    use Queueable, SerializesModels;

    public $nombreGrupo;
    public $nombrePropietario;
    public $usuariosCompartidos;
    public $cantidadUsuarios;

    /**
     * Create a new message instance.
     */
    public function __construct($nombreGrupo, $nombrePropietario, $usuariosCompartidos)
    {
        $this->nombreGrupo = $nombreGrupo;
        $this->nombrePropietario = $nombrePropietario;
        $this->usuariosCompartidos = $usuariosCompartidos;
        $this->cantidadUsuarios = count($usuariosCompartidos);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Grupo Compartido: ' . $this->nombreGrupo,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.grupo-compartido-propietario',
            with: [
                'nombreGrupo' => $this->nombreGrupo,
                'nombrePropietario' => $this->nombrePropietario,
                'usuariosCompartidos' => $this->usuariosCompartidos,
                'cantidadUsuarios' => $this->cantidadUsuarios,
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