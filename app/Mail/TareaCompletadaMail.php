<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class TareaCompletadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $nombreUsuario;
    public $nombreCompletador;
    public $tarea;
    public $grupo;
    public $lista;
    public $urlTarea;
    public $esCreador;

    /**
     * Create a new message instance.
     */
    public function __construct($nombreUsuario, $nombreCompletador, $tarea, $grupo, $lista, $esCreador = false)
    {
        $this->nombreUsuario = $nombreUsuario;
        $this->nombreCompletador = $nombreCompletador;
        $this->tarea = $tarea;
        $this->grupo = $grupo;
        $this->lista = $lista;
        $this->esCreador = $esCreador;
        
        // URL directa al tablero con la tarea
        $this->urlTarea = 'https://crmbbm.preubasbbm.com/tasks/tareas/tablero/' . $grupo->id;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Tarea Completada: ' . $this->tarea->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tarea-completada',
            with: [
                'nombreUsuario' => $this->nombreUsuario,
                'nombreCompletador' => $this->nombreCompletador,
                'tarea' => $this->tarea,
                'grupo' => $this->grupo,
                'lista' => $this->lista,
                'urlTarea' => $this->urlTarea,
                'esCreador' => $this->esCreador,
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