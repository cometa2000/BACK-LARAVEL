<?php

namespace App\Http\Controllers\Configuration;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Configuration\Sucursale;
use App\Models\User;

class SucusaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get("search");

        $sucursales = Sucursale::where("name","like","%".$search."%")
                              ->orderBy("id","desc")
                              ->paginate(25);

        return response()->json([
            "total" => $sucursales->total(),
            "sucursales" => $sucursales->map(function($sucursal) {
                return [
                    "id" => $sucursal->id,
                    "name" => $sucursal->name,
                    "address" => $sucursal->address,
                    "state" => $sucursal->state,
                    "created_at" => $sucursal->created_at->format("Y-m-d h:i A")
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $is_exits_sucursal = Sucursale::where("name",$request->name)->first();
        if($is_exits_sucursal){
            return response()->json([
                "message" => 403,
                "message_text" => "El nombre de la sucursal ya existe"
            ]);
        }
        
        $sucursal = Sucursale::create($request->all());
        
        return response()->json([
            "message" => 200,
            "sucursal" => [
                "id" => $sucursal->id,
                "name" => $sucursal->name,
                "address" => $sucursal->address,
                "state" => $sucursal->state ?? 1,
                "created_at" => $sucursal->created_at->format("Y-m-d h:i A")
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $is_exits_sucursal = Sucursale::where("name",$request->name)
                            ->where("id","<>",$id)->first();
        if($is_exits_sucursal){
            return response()->json([
                "message" => 403,
                "message_text" => "El nombre de la sucursal ya existe"
            ]);
        }
        
        $sucursal = Sucursale::findOrFail($id);
        $sucursal->update($request->all());
        
        return response()->json([
            "message" => 200,
            "sucursal" => [
                "id" => $sucursal->id,
                "name" => $sucursal->name,
                "address" => $sucursal->address,
                "state" => $sucursal->state,
                "created_at" => $sucursal->created_at->format("Y-m-d h:i A")
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * 
     * ⚠️ VALIDACIÓN DE SEGURIDAD:
     * No permite borrar la sucursal si existen usuarios asociados
     */
    public function destroy(string $id)
    {
        $sucursal = Sucursale::findOrFail($id);
        
        // ========================================
        // VALIDACIÓN: Verificar si hay usuarios en esta sucursal
        // ========================================
        $usersCount = User::where('sucursale_id', $id)->count();
        
        if ($usersCount > 0) {
            return response()->json([
                "message" => 403,
                "message_text" => "No se puede eliminar la sucursal porque existen {$usersCount} usuario(s) asignado(s) a esta sucursal. Por favor, reasigne los usuarios a otra sucursal antes de continuar."
            ], 403);
        }

        // ========================================
        // VALIDACIÓN ADICIONAL: Verificar otras relaciones si existen
        // ========================================
        // Si tienes otras tablas relacionadas con sucursales (proformas, ventas, etc.)
        // puedes agregar más validaciones aquí:
        
        // Ejemplo:
        // $proformasCount = Proforma::where('sucursale_id', $id)->count();
        // if ($proformasCount > 0) {
        //     return response()->json([
        //         "message" => 403,
        //         "message_text" => "No se puede eliminar la sucursal porque tiene {$proformasCount} proforma(s) asociada(s)."
        //     ], 403);
        // }

        // ========================================
        // SOFT DELETE: Borrado lógico
        // ========================================
        // Con SoftDeletes, la sucursal no se borra físicamente de la BD
        // Solo se marca con deleted_at
        $sucursal->delete();
        
        return response()->json([
            "message" => 200,
            "message_text" => "La sucursal se eliminó correctamente"
        ]);
    }

    /**
     * Restaurar una sucursal eliminada (opcional)
     * Agregar esta ruta en api.php si se necesita:
     * Route::post('/sucursales/{id}/restore', [SucusaleController::class, 'restore']);
     */
    public function restore(string $id)
    {
        $sucursal = Sucursale::withTrashed()->findOrFail($id);
        $sucursal->restore();

        return response()->json([
            "message" => 200,
            "message_text" => "La sucursal se restauró correctamente",
            "sucursal" => [
                "id" => $sucursal->id,
                "name" => $sucursal->name,
                "address" => $sucursal->address,
                "state" => $sucursal->state,
                "created_at" => $sucursal->created_at->format("Y-m-d h:i A")
            ]
        ]);
    }

    /**
     * Forzar eliminación permanente (opcional, usar con cuidado)
     */
    public function forceDelete(string $id)
    {
        $sucursal = Sucursale::withTrashed()->findOrFail($id);
        
        // Doble verificación antes de borrado permanente
        $usersCount = User::where('sucursale_id', $id)->count();
        
        if ($usersCount > 0) {
            return response()->json([
                "message" => 403,
                "message_text" => "No se puede eliminar permanentemente la sucursal porque existen usuarios asignados."
            ], 403);
        }

        $sucursal->forceDelete();

        return response()->json([
            "message" => 200,
            "message_text" => "La sucursal se eliminó permanentemente"
        ]);
    }

    /**
     * Obtener lista de sucursales eliminadas (opcional)
     * Útil para un módulo de "papelera" o recuperación
     */
    public function getTrashed(Request $request)
    {
        $search = $request->get("search", "");

        $sucursales = Sucursale::onlyTrashed()
                              ->where("name","like","%".$search."%")
                              ->orderBy("deleted_at","desc")
                              ->paginate(25);

        return response()->json([
            "total" => $sucursales->total(),
            "sucursales" => $sucursales->map(function($sucursal) {
                return [
                    "id" => $sucursal->id,
                    "name" => $sucursal->name,
                    "address" => $sucursal->address,
                    "state" => $sucursal->state,
                    "created_at" => $sucursal->created_at->format("Y-m-d h:i A"),
                    "deleted_at" => $sucursal->deleted_at->format("Y-m-d h:i A")
                ];
            }),
        ]);
    }
}