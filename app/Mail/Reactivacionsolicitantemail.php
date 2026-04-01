<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

/**
 * Correo 1 de 3 — Reactivación de tarea vencida
 * Destinatario: el usuario compartido que hizo clic en "Solicitar reactivación"
 * Mensaje: confirmamos que tu solicitud fue enviada al propietario del grupo.
 */
class ReactivacionSolicitanteMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $nombreSolicitante;
    public string $nombreTarea;
    public string $fechaVencimiento;   // Fecha original de vencimiento formateada
    public string $nombreGrupo;
    public string $urlTarea;

    public function __construct(
        string $nombreSolicitante,
        string $nombreTarea,
        string $fechaVencimiento,
        string $nombreGrupo,
        int    $grupoId
    ) {
        $this->nombreSolicitante = $nombreSolicitante;
        $this->nombreTarea       = $nombreTarea;
        $this->fechaVencimiento  = $fechaVencimiento;
        $this->nombreGrupo       = $nombreGrupo;
        $this->urlTarea          = 'https://crm-angular.preubasbbm.com/tasks/tareas/tablero/' . $grupoId;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Solicitud de reactivación enviada — ' . $this->nombreTarea,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reactivacion-solicitante',
            with: [
                'nombreSolicitante' => $this->nombreSolicitante,
                'nombreTarea'       => $this->nombreTarea,
                'fechaVencimiento'  => $this->fechaVencimiento,
                'nombreGrupo'       => $this->nombreGrupo,
                'urlTarea'          => $this->urlTarea,
            ]
        );
    }

    public function attachments(): array { return []; }
}