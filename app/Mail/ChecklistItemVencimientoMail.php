<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class ChecklistItemVencimientoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $nombreUsuario;
    public $nombreItem;
    public $nombreChecklist;
    public $nombreTarea;
    public $nombreGrupo;
    public $grupoId;
    public $fechaVencimiento;
    public $diasRestantes;      // 0 = hoy, negativo = ya vencido, positivo = días restantes

    public function __construct(
        $nombreUsuario,
        $nombreItem,
        $nombreChecklist,
        $nombreTarea,
        $nombreGrupo,
        $grupoId,
        $fechaVencimiento,
        $diasRestantes
    ) {
        $this->nombreUsuario    = $nombreUsuario;
        $this->nombreItem       = $nombreItem;
        $this->nombreChecklist  = $nombreChecklist;
        $this->nombreTarea      = $nombreTarea;
        $this->nombreGrupo      = $nombreGrupo;
        $this->grupoId          = $grupoId;
        $this->fechaVencimiento = $fechaVencimiento;
        $this->diasRestantes    = $diasRestantes;
    }

    public function envelope(): Envelope
    {
        $asunto = $this->diasRestantes <= 0
            ? 'Elemento vencido: ' . $this->nombreItem
            : 'Vencimiento próximo (' . $this->diasRestantes . ' día(s)): ' . $this->nombreItem;

        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: $asunto,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.checklist-item-vencimiento',
            with: [
                'nombreUsuario'    => $this->nombreUsuario,
                'nombreItem'       => $this->nombreItem,
                'nombreChecklist'  => $this->nombreChecklist,
                'nombreTarea'      => $this->nombreTarea,
                'nombreGrupo'      => $this->nombreGrupo,
                'fechaVencimiento' => $this->fechaVencimiento,
                'diasRestantes'    => $this->diasRestantes,
                'esVencido'        => $this->diasRestantes <= 0,
                'urlTablero'       => 'https://crmbbm.preubasbbm.com/tasks/tareas/tablero/' . $this->grupoId,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}