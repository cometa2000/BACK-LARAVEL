<?php

namespace App\Models\documents;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentoView extends Model
{
    use HasFactory;

    protected $fillable = [
        'documento_id',
        'user_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function documento()
    {
        return $this->belongsTo(Documentos::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}