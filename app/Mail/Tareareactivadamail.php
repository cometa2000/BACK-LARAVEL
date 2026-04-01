<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

/**
 * Correo 3 de 4 — Reactivación de tarea vencida
 * Destinatario: usuarios asignados a la tarea
 * Mensaje: la tarea fue reactivada por el propietario, ya pueden continuar trabajando.
 *
 * Se envía cuando el propietario modifica o elimina la fecha de vencimiento
 * de una tarea que estaba vencida (is_overdue era true antes del update).
 */
class TareaReactivadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $nombreDestinatario;
    public string $nombrePropietario;
    public string $nombreTarea;
    public string $nuevaFechaVencimiento; // Nueva fecha o "Sin fecha asignada"
    public string $nombreGrupo;
    public string $urlTarea;
    public string $accion;               // 'editada' | 'eliminada'

    public function __construct(
        string $nombreDestinatario,
        string $nombrePropietario,
        string $nombreTarea,
        string $nuevaFechaVencimiento,
        string $nombreGrupo,
        int    $grupoId,
        string $accion = 'editada'
    ) {
        $this->nombreDestinatario    = $nombreDestinatario;
        $this->nombrePropietario     = $nombrePropietario;
        $this->nombreTarea           = $nombreTarea;
        $this->nuevaFechaVencimiento = $nuevaFechaVencimiento;
        $this->nombreGrupo           = $nombreGrupo;
        $this->urlTarea              = 'https://crm-angular.preubasbbm.com/tasks/tareas/tablero/' . $grupoId;
        $this->accion                = $accion;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: '¡Tarea reactivada! — ' . $this->nombreTarea,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tarea-reactivada',
            with: [
                'nombreDestinatario'    => $this->nombreDestinatario,
                'nombrePropietario'     => $this->nombrePropietario,
                'nombreTarea'           => $this->nombreTarea,
                'nuevaFechaVencimiento' => $this->nuevaFechaVencimiento,
                'nombreGrupo'           => $this->nombreGrupo,
                'urlTarea'              => $this->urlTarea,
                'accion'                => $this->accion,
            ]
        );
    }

    public function attachments(): array { return []; }
}