<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role; // Usar nuestro modelo extendido en lugar del de Spatie
use App\Models\User;

class RolePermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize("viewAny",Role::class);
        $search = $request->get("search");

        // Incluir roles eliminados si es necesario: ->withTrashed()
        $roles = Role::with(["permissions"])
                    ->where("name","like","%".$search."%")
                    ->orderBy("id","desc")
                    ->paginate(25);

        return response()->json([
            "total" => $roles->total(),
            "roles" => $roles->map(function($rol) {
                $rol->permission_pluck = $rol->permissions->pluck("name");
                $rol->created_format_at = $rol->created_at->format("Y-m-d h:i A");
                return $rol;
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $IS_ROLE = Role::where("name",$request->name)->first();
        if($IS_ROLE){
            return response()->json([
                "message" => 403,
                "message_text" => "EL ROL YA EXISTE"
            ]);
        }
        
        $role = Role::create([
            'guard_name' => 'api',
            'name' => $request->name
        ]);
        
        // [["id" => 1,"name" => "egreso"],["id" => 2,"name" => "ingreso"],["id" => 3,"name" => "close_caja"]]
        // ["egreso","ingreso","close_caja"]
        foreach ($request->permisions as $key => $permision) {
           $role->givePermissionTo($permision);
        }

        return response()->json([
            "message" => 200,
            "role" => [
                "id" => $role->id,
                "permission" => $role->permissions,
                "permission_pluck" => $role->permissions->pluck("name"),
                "created_format_at" => $role->created_at->format("Y-m-d h:i A"),
                "name" => $role->name,
            ]
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
        $IS_ROLE = Role::where("name",$request->name)->where("id","<>",$id)->first();
        if($IS_ROLE){
            return response()->json([
                "message" => 403,
                "message_text" => "EL ROL YA EXISTE"
            ]);
        }
        
        $role = Role::findOrFail($id);
        $role->update($request->all());
        
        // [["id" => 1,"name" => "egreso"],["id" => 2,"name" => "ingreso"],["id" => 3,"name" => "close_caja"]]
        // ["egreso","ingreso","close_caja"]
        $role->syncPermissions($request->permisions);

        return response()->json([
            "message" => 200,
            "role" => [
                "id" => $role->id,
                "permission" => $role->permissions,
                "permission_pluck" => $role->permissions->pluck("name"),
                "created_format_at" => $role->created_at->format("Y-m-d h:i A"),
                "name" => $role->name,
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * 
     * ⚠️ VALIDACIÓN DE SEGURIDAD:
     * No permite borrar el rol si existen usuarios asociados
     */
    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);
        
        // ========================================
        // VALIDACIÓN: Verificar si hay usuarios con este rol
        // ========================================
        $usersCount = User::where('role_id', $id)->count();
        
        if ($usersCount > 0) {
            return response()->json([
                "message" => 403,
                "message_text" => "No se puede eliminar el rol porque existen {$usersCount} usuario(s) asignado(s) a este rol. Por favor, reasigne los usuarios a otro rol antes de continuar."
            ], 403);
        }

        // ========================================
        // SOFT DELETE: Borrado lógico
        // ========================================
        // Con SoftDeletes, el rol no se borra físicamente de la BD
        // Solo se marca con deleted_at
        $role->delete();

        return response()->json([
            "message" => 200,
            "message_text" => "El rol se eliminó correctamente"
        ]);
    }

    /**
     * Restaurar un rol eliminado (opcional)
     * Agregar esta ruta en api.php si se necesita:
     * Route::post('/roles/{id}/restore', [RolePermissionController::class, 'restore']);
     */
    public function restore(string $id)
    {
        $role = Role::withTrashed()->findOrFail($id);
        $role->restore();

        return response()->json([
            "message" => 200,
            "message_text" => "El rol se restauró correctamente",
            "role" => [
                "id" => $role->id,
                "permission_pluck" => $role->permissions->pluck("name"),
                "created_format_at" => $role->created_at->format("Y-m-d h:i A"),
                "name" => $role->name,
            ]
        ]);
    }

    /**
     * Forzar eliminación permanente (opcional, usar con cuidado)
     */
    public function forceDelete(string $id)
    {
        $role = Role::withTrashed()->findOrFail($id);
        
        // Doble verificación antes de borrado permanente
        $usersCount = User::where('role_id', $id)->count();
        
        if ($usersCount > 0) {
            return response()->json([
                "message" => 403,
                "message_text" => "No se puede eliminar permanentemente el rol porque existen usuarios asignados."
            ], 403);
        }

        $role->forceDelete();

        return response()->json([
            "message" => 200,
            "message_text" => "El rol se eliminó permanentemente"
        ]);
    }
}