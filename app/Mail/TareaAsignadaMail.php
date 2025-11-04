<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class TareaAsignadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $nombreUsuario;
    public $nombreAsignador;
    public $tarea;
    public $grupo;
    public $lista;
    public $urlTarea;

    /**
     * Create a new message instance.
     */
    public function __construct($nombreUsuario, $nombreAsignador, $tarea, $grupo, $lista)
    {
        $this->nombreUsuario = $nombreUsuario;
        $this->nombreAsignador = $nombreAsignador;
        $this->tarea = $tarea;
        $this->grupo = $grupo;
        $this->lista = $lista;
        
        // URL directa al tablero con la tarea
        $this->urlTarea = env('APP_URL') . '/tasks/tareas/tablero/' . $grupo->id;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Te han asignado una nueva tarea: ' . $this->tarea->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tarea-asignada',
            with: [
                'nombreUsuario' => $this->nombreUsuario,
                'nombreAsignador' => $this->nombreAsignador,
                'tarea' => $this->tarea,
                'grupo' => $this->grupo,
                'lista' => $this->lista,
                'urlTarea' => $this->urlTarea,
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
