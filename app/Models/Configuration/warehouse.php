<?php

namespace App\Models\configuration;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class warehouse extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        "name",
        "address",
        "state",
        "sucursale_id"
    ];

    public function setCreatedAtAttribute($value) {
        date_default_timezone_set('America/Mexico_City');
        $this->attributes["created_at"] = Carbon::now();
    }
    public function setUpdatedAtAttribute($value) {
        date_default_timezone_set('America/Mexico_City');
        $this->attributes["updated_at"] = Carbon::now();
    }

    public function sucursale(){
        return $this->belongsTo(Sucursale::class,"sucursale_id");
    }
}
