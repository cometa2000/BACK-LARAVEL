<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class ChecklistItemAsignadoAsignadorMail extends Mailable
{
    use Queueable, SerializesModels;

    public $nombreAsignador;
    public $usuariosAsignados;   // array de ['name' => ..., 'email' => ...]
    public $nombreItem;
    public $nombreChecklist;
    public $nombreTarea;
    public $nombreGrupo;
    public $grupoId;

    public function __construct(
        $nombreAsignador,
        $usuariosAsignados,
        $nombreItem,
        $nombreChecklist,
        $nombreTarea,
        $nombreGrupo,
        $grupoId
    ) {
        $this->nombreAsignador  = $nombreAsignador;
        $this->usuariosAsignados = $usuariosAsignados;
        $this->nombreItem       = $nombreItem;
        $this->nombreChecklist  = $nombreChecklist;
        $this->nombreTarea      = $nombreTarea;
        $this->nombreGrupo      = $nombreGrupo;
        $this->grupoId          = $grupoId;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Asignaste miembros al elemento: ' . $this->nombreItem,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.checklist-item-asignado-asignador',
            with: [
                'nombreAsignador'   => $this->nombreAsignador,
                'usuariosAsignados' => $this->usuariosAsignados,
                'cantidadUsuarios'  => count($this->usuariosAsignados),
                'nombreItem'        => $this->nombreItem,
                'nombreChecklist'   => $this->nombreChecklist,
                'nombreTarea'       => $this->nombreTarea,
                'nombreGrupo'       => $this->nombreGrupo,
                'grupoId'           => $this->grupoId,
                'urlTablero'        => 'https://crmbbm.preubasbbm.com/tasks/tareas/tablero/' . $this->grupoId,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}