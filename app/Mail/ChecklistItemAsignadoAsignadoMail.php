<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class ChecklistItemAsignadoAsignadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $nombreAsignado;
    public $nombreAsignador;
    public $nombreItem;
    public $nombreChecklist;
    public $nombreTarea;
    public $nombreGrupo;
    public $grupoId;
    public $fechaVencimiento;   // nullable

    public function __construct(
        $nombreAsignado,
        $nombreAsignador,
        $nombreItem,
        $nombreChecklist,
        $nombreTarea,
        $nombreGrupo,
        $grupoId,
        $fechaVencimiento = null
    ) {
        $this->nombreAsignado   = $nombreAsignado;
        $this->nombreAsignador  = $nombreAsignador;
        $this->nombreItem       = $nombreItem;
        $this->nombreChecklist  = $nombreChecklist;
        $this->nombreTarea      = $nombreTarea;
        $this->nombreGrupo      = $nombreGrupo;
        $this->grupoId          = $grupoId;
        $this->fechaVencimiento = $fechaVencimiento;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Te han asignado a un elemento: ' . $this->nombreItem,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.checklist-item-asignado-asignado',
            with: [
                'nombreAsignado'    => $this->nombreAsignado,
                'nombreAsignador'   => $this->nombreAsignador,
                'nombreItem'        => $this->nombreItem,
                'nombreChecklist'   => $this->nombreChecklist,
                'nombreTarea'       => $this->nombreTarea,
                'nombreGrupo'       => $this->nombreGrupo,
                'fechaVencimiento'  => $this->fechaVencimiento,
                'urlTablero'        => 'https://crmbbm.preubasbbm.com/tasks/tareas/tablero/' . $this->grupoId,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}