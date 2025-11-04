<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class TareaVencimientoProximoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tarea;
    public $usuario;
    public $diasRestantes;

    /**
     * Create a new message instance.
     */
    public function __construct($tarea, $usuario, $diasRestantes)
    {
        $this->tarea = $tarea;
        $this->usuario = $usuario;
        $this->diasRestantes = $diasRestantes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->diasRestantes == 1 
            ? '⏰ Tarea vence mañana: ' . $this->tarea->name 
            : '⏰ Tarea vence en ' . $this->diasRestantes . ' días: ' . $this->tarea->name;

        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tarea-vencimiento-proximo',
            with: [
                'tarea' => $this->tarea,
                'usuario' => $this->usuario,
                'diasRestantes' => $this->diasRestantes,
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