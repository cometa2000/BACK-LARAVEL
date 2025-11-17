<?php

namespace App\Models\documents;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Configuration\Sucursale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Documentos extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $fillable = [
        "name",         // Nombre de documento o carpeta
        "type",         // 'file' o 'folder'
        "parent_id",    // Relación recursiva para carpetas
        "order",        // Orden dentro de la carpeta
        "sucursale_id", // A qué sucursal pertenece
        "user_id",      // Quién lo subió o creó
        "file_path",    // Ruta física o almacenamiento (solo si es 'file')
        "mime_type",    // Tipo de archivo (PDF, JPG, etc.)
        "size",         // Tamaño en bytes del archivo
        "description",  // Descripción opcional
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function setCreatedAtAttribute($value) {
        date_default_timezone_set('America/Mexico_City');
        $this->attributes["created_at"] = Carbon::now();
    }

    public function setUpdatedAtAttribute($value) {
        date_default_timezone_set('America/Mexico_City');
        $this->attributes["updated_at"] = Carbon::now();
    }

    // Relaciones básicas
    public function sucursale(){
        return $this->belongsTo(Sucursale::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    // ========== RELACIONES PARA SISTEMA DE CARPETAS ==========
    
    /**
     * Carpeta padre
     */
    public function parent()
    {
        return $this->belongsTo(Documentos::class, 'parent_id');
    }

    /**
     * Archivos/Carpetas hijos
     */
    public function children()
    {
        return $this->hasMany(Documentos::class, 'parent_id')->orderBy('order', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Todos los hijos recursivamente (para obtener toda la jerarquía)
     */
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    // ========== MÉTODOS ÚTILES ==========

    /**
     * Verifica si es una carpeta
     */
    public function isFolder()
    {
        return $this->type === 'folder';
    }

    /**
     * Verifica si es un archivo
     */
    public function isFile()
    {
        return $this->type === 'file';
    }

    /**
     * Obtiene la ruta completa (breadcrumb) desde la raíz
     */
    public function getPath()
    {
        $path = [];
        $current = $this;
        
        while ($current) {
            array_unshift($path, [
                'id' => $current->id,
                'name' => $current->name,
                'type' => $current->type
            ]);
            $current = $current->parent;
        }
        
        return $path;
    }

    /**
     * Cuenta todos los archivos dentro de una carpeta (recursivamente)
     */
    public function countAllFiles()
    {
        if ($this->isFile()) {
            return 1;
        }

        $count = 0;
        foreach ($this->children as $child) {
            if ($child->isFile()) {
                $count++;
            } else {
                $count += $child->countAllFiles();
            }
        }
        
        return $count;
    }

    /**
     * Verifica si un documento puede ser movido a una carpeta destino
     * (evita mover una carpeta dentro de sí misma o de sus hijos)
     */
    public function canMoveTo($targetParentId)
    {
        if ($targetParentId === null) {
            return true; // Mover a la raíz siempre está permitido
        }

        if ($this->id === $targetParentId) {
            return false; // No puede moverse a sí mismo
        }

        // Verificar que no sea un hijo del elemento a mover
        $current = Documentos::find($targetParentId);
        while ($current) {
            if ($current->id === $this->id) {
                return false; // El destino es un hijo del elemento actual
            }
            $current = $current->parent;
        }

        return true;
    }

    /**
     * Scope para obtener solo carpetas
     */
    public function scopeFolders($query)
    {
        return $query->where('type', 'folder');
    }

    /**
     * Scope para obtener solo archivos
     */
    public function scopeFiles($query)
    {
        return $query->where('type', 'file');
    }

    /**
     * Scope para obtener elementos en la raíz (sin padre)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}