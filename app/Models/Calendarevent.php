<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'all_day',
        'color',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'all_day' => 'boolean',
    ];

    // Relación con el usuario que creó el evento
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Formato para FullCalendar
    public function toFullCalendarFormat()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'start' => $this->start_date->format('Y-m-d\TH:i:s'),
            'end' => $this->end_date ? $this->end_date->format('Y-m-d\TH:i:s') : null,
            'allDay' => $this->all_day,
            'backgroundColor' => $this->color,
            'borderColor' => $this->color,
            'extendedProps' => [
                'description' => $this->description,
                'created_by' => $this->created_by,
                'creator_name' => $this->creator ? $this->creator->name : 'Usuario',
            ]
        ];
    }
}