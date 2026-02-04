<?php

namespace App\Models\tasks;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lista extends Model
{
    use HasFactory;

    protected $table = 'listas';

    protected $fillable = [
        'name',
        'orden',
        'grupo_id',
    ];
    

    protected $casts = ['orden' => 'integer'];

    public function grupo()
    {
        return $this->belongsTo(Grupos::class, 'grupo_id');
    }

    public function tareas()
    {
        return $this->hasMany(Tareas::class, 'lista_id');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

}
