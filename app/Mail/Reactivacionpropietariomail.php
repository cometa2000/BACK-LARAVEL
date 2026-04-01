<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

/**
 * Correo 2 de 3 — Reactivación de tarea vencida
 * Destinatario: propietario del grupo
 * Mensaje: recibiste una solicitud de reactivación de tarea vencida.
 */
class ReactivacionPropietarioMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $nombrePropietario;
    public string $nombreSolicitante;
    public string $nombreTarea;
    public string $fechaVencimiento;    // Fecha original de vencimiento formateada
    public string $fechaSolicitud;      // Fecha y hora en que se hizo la solicitud
    public string $nombreGrupo;
    public string $urlTarea;

    public function __construct(
        string $nombrePropietario,
        string $nombreSolicitante,
        string $nombreTarea,
        string $fechaVencimiento,
        string $fechaSolicitud,
        string $nombreGrupo,
        int    $grupoId
    ) {
        $this->nombrePropietario = $nombrePropietario;
        $this->nombreSolicitante = $nombreSolicitante;
        $this->nombreTarea       = $nombreTarea;
        $this->fechaVencimiento  = $fechaVencimiento;
        $this->fechaSolicitud    = $fechaSolicitud;
        $this->nombreGrupo       = $nombreGrupo;
        $this->urlTarea          = 'https://crm-angular.preubasbbm.com/tasks/tareas/tablero/' . $grupoId;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Solicitud de reactivación de tarea — ' . $this->nombreTarea,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reactivacion-propietario',
            with: [
                'nombrePropietario' => $this->nombrePropietario,
                'nombreSolicitante' => $this->nombreSolicitante,
                'nombreTarea'       => $this->nombreTarea,
                'fechaVencimiento'  => $this->fechaVencimiento,
                'fechaSolicitud'    => $this->fechaSolicitud,
                'nombreGrupo'       => $this->nombreGrupo,
                'urlTarea'          => $this->urlTarea,
            ]
        );
    }

    public function attachments(): array { return []; }
}