<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

/**
 * Correo 4 de 4 — Confirmación de reactivación al propietario del grupo.
 *
 * Se envía al PROPIETARIO del grupo inmediatamente después de que él mismo
 * modifica o elimina la fecha de vencimiento de una tarea vencida.
 *
 * Mensaje: "Reactivaste la tarea exitosamente, tu grupo puede continuar."
 */
class ReactivacionConfirmadaPropietarioMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $nombrePropietario;
    public string $nombreTarea;
    public string $nuevaFecha;       // "dd/mm/YYYY" o "Sin fecha asignada"
    public string $nombreGrupo;
    public int    $totalMiembros;    // Cuántos miembros fueron notificados
    public string $urlTarea;
    public string $accion;           // 'editada' | 'eliminada'

    public function __construct(
        string $nombrePropietario,
        string $nombreTarea,
        string $nuevaFecha,
        string $nombreGrupo,
        int    $grupoId,
        int    $totalMiembros = 0,
        string $accion = 'editada'
    ) {
        $this->nombrePropietario = $nombrePropietario;
        $this->nombreTarea       = $nombreTarea;
        $this->nuevaFecha        = $nuevaFecha;
        $this->nombreGrupo       = $nombreGrupo;
        $this->urlTarea          = 'https://crm-angular.preubasbbm.com/tasks/tareas/tablero/' . $grupoId;
        $this->totalMiembros     = $totalMiembros;
        $this->accion            = $accion;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: '✅ Tarea reactivada exitosamente — ' . $this->nombreTarea,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reactivacion-confirmada-propietario',
            with: [
                'nombrePropietario' => $this->nombrePropietario,
                'nombreTarea'       => $this->nombreTarea,
                'nuevaFecha'        => $this->nuevaFecha,
                'nombreGrupo'       => $this->nombreGrupo,
                'urlTarea'          => $this->urlTarea,
                'totalMiembros'     => $this->totalMiembros,
                'accion'            => $this->accion,
            ]
        );
    }

    public function attachments(): array { return []; }
}