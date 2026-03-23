<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class WorkspaceCreadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $nombreUsuario;
    public $nombreWorkspace;
    public $descripcionWorkspace;
    public $colorWorkspace;

    public function __construct($nombreUsuario, $nombreWorkspace, $descripcionWorkspace = null, $colorWorkspace = '#667eea')
    {
        $this->nombreUsuario      = $nombreUsuario;
        $this->nombreWorkspace    = $nombreWorkspace;
        $this->descripcionWorkspace = $descripcionWorkspace;
        $this->colorWorkspace     = $colorWorkspace;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Espacio de trabajo creado: ' . $this->nombreWorkspace,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.workspace-creado',
            with: [
                'nombreUsuario'       => $this->nombreUsuario,
                'nombreWorkspace'     => $this->nombreWorkspace,
                'descripcionWorkspace'=> $this->descripcionWorkspace,
                'colorWorkspace'      => $this->colorWorkspace,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}