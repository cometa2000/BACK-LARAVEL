<?php

namespace App\Http\Controllers\sistema_de_tickets;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\sistema_de_tickets\TicketArea;
use App\Models\User;

/**
 * CRUD de Áreas de la sede principal para el sistema de tickets.
 *
 * ACCESO: exclusivo para role_id = 1 (Super-Admin).
 *
 * El sistema NO usa la tabla pivot model_has_roles de Spatie.
 * Los roles se verifican directamente por users.role_id.
 *
 * FIX 403: se reemplazó $user->hasRole('Super-Admin')
 *          por     $user->role_id == self::ROL_SUPER_ADMIN
 *
 * Rutas (prefijo /sistema-de-tickets):
 *   GET    /areas-usuarios-sede     → usuariosSede  ← IMPORTANTE: ruta propia, no comparte prefijo con /{id}
 *   GET    /areas                   → index
 *   POST   /areas                   → store
 *   PUT    /areas/{id}              → update
 *   DELETE /areas/{id}              → destroy
 */
class TicketAreasController extends Controller
{
    /** ID de la sucursal "hueso" — sede principal */
    const SEDE_PRINCIPAL_ID   = 5;

    /** role_id del Super-Admin en la tabla users */
    const ROL_SUPER_ADMIN     = 1;

    // ================================================================
    // INDEX — Listar todas las áreas (activas e inactivas / soft deleted)
    // ================================================================
    public function index(Request $request)
    {
        try {
            $user = auth('api')->user();

            if ((int)$user->role_id !== self::ROL_SUPER_ADMIN) {
                return response()->json(['message' => 403, 'error' => 'Sin permiso'], 403);
            }

            $areas = TicketArea::withTrashed()
                ->with('responsable:id,name,surname,avatar')
                ->orderBy('nombre')
                ->get()
                ->map(fn($a) => $this->format($a));

            return response()->json(['message' => 200, 'areas' => $areas]);

        } catch (\Exception $e) {
            Log::error('TicketAreasController@index', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al listar áreas'], 500);
        }
    }

    // ================================================================
    // STORE — Crear nueva área
    // ================================================================
    public function store(Request $request)
    {
        try {
            $user = auth('api')->user();

            if ((int)$user->role_id !== self::ROL_SUPER_ADMIN) {
                return response()->json(['message' => 403, 'error' => 'Sin permiso'], 403);
            }

            $request->validate([
                'nombre'         => 'required|string|max:150',
                'descripcion'    => 'nullable|string|max:500',
                'responsable_id' => 'required|exists:users,id',
                'activo'         => 'boolean',
            ]);

            // El responsable DEBE ser de la sede principal (sucursale_id = 5)
            $responsable = User::findOrFail($request->responsable_id);
            if ((int)$responsable->sucursale_id !== self::SEDE_PRINCIPAL_ID) {
                return response()->json([
                    'message' => 422,
                    'error'   => 'El responsable debe pertenecer a la sede principal (sucursal Hueso).',
                ], 422);
            }

            $area = TicketArea::create([
                'nombre'         => trim($request->nombre),
                'descripcion'    => $request->descripcion,
                'responsable_id' => $request->responsable_id,
                'activo'         => $request->boolean('activo', true),
            ]);

            $area->load('responsable:id,name,surname,avatar');

            return response()->json([
                'message' => 200,
                'area'    => $this->format($area),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 422, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('TicketAreasController@store', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al crear área'], 500);
        }
    }

    // ================================================================
    // UPDATE — Editar área existente (también restaura si estaba eliminada)
    // ================================================================
    public function update(Request $request, string $id)
    {
        try {
            $user = auth('api')->user();

            if ((int)$user->role_id !== self::ROL_SUPER_ADMIN) {
                return response()->json(['message' => 403, 'error' => 'Sin permiso'], 403);
            }

            $area = TicketArea::withTrashed()->findOrFail($id);

            $request->validate([
                'nombre'         => 'sometimes|string|max:150',
                'descripcion'    => 'nullable|string|max:500',
                'responsable_id' => 'sometimes|exists:users,id',
                'activo'         => 'boolean',
            ]);

            // Si cambia el responsable, validar que sea de la sede
            if ($request->filled('responsable_id')) {
                $responsable = User::findOrFail($request->responsable_id);
                if ((int)$responsable->sucursale_id !== self::SEDE_PRINCIPAL_ID) {
                    return response()->json([
                        'message' => 422,
                        'error'   => 'El responsable debe pertenecer a la sede principal (sucursal Hueso).',
                    ], 422);
                }
            }

            // Construir solo los campos que vienen en el request
            $data = [];
            if ($request->filled('nombre'))         $data['nombre']         = trim($request->nombre);
            if ($request->has('descripcion'))        $data['descripcion']    = $request->descripcion;
            if ($request->filled('responsable_id')) $data['responsable_id'] = $request->responsable_id;
            if ($request->has('activo'))             $data['activo']         = $request->boolean('activo');

            if (!empty($data)) {
                $area->update($data);
            }

            // Restaurar si estaba en soft-delete
            if ($area->trashed()) {
                $area->restore();
            }

            $area->load('responsable:id,name,surname,avatar');

            return response()->json([
                'message' => 200,
                'area'    => $this->format($area),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 422, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('TicketAreasController@update', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al actualizar área'], 500);
        }
    }

    // ================================================================
    // DESTROY — Soft delete
    // ================================================================
    public function destroy(string $id)
    {
        try {
            $user = auth('api')->user();

            if ((int)$user->role_id !== self::ROL_SUPER_ADMIN) {
                return response()->json(['message' => 403, 'error' => 'Sin permiso'], 403);
            }

            $area = TicketArea::findOrFail($id);
            $area->delete();

            return response()->json([
                'message'      => 200,
                'message_text' => 'Área eliminada correctamente',
            ]);

        } catch (\Exception $e) {
            Log::error('TicketAreasController@destroy', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al eliminar área'], 500);
        }
    }

    // ================================================================
    // USUARIOS SEDE — Usuarios de sucursale_id = 5 para el selector
    //
    // RUTA: GET /sistema-de-tickets/areas-usuarios-sede
    // (ruta independiente para evitar conflicto con /areas/{id})
    // ================================================================
    public function usuariosSede()
    {
        try {
            $user = auth('api')->user();

            if ((int)$user->role_id !== self::ROL_SUPER_ADMIN) {
                return response()->json(['message' => 403, 'error' => 'Sin permiso'], 403);
            }

            $usuarios = User::where('sucursale_id', self::SEDE_PRINCIPAL_ID)
                ->whereNull('deleted_at')
                ->with('roles:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'surname', 'avatar', 'role_id'])
                ->map(fn($u) => [
                    'id'     => $u->id,
                    'nombre' => trim($u->name . ' ' . ($u->surname ?? '')),
                    'avatar' => $u->avatar,
                    'rol'    => $u->roles->first()?->name ?? '(sin rol)',
                ]);

            return response()->json(['message' => 200, 'usuarios' => $usuarios]);

        } catch (\Exception $e) {
            Log::error('TicketAreasController@usuariosSede', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al obtener usuarios'], 500);
        }
    }

    // ================================================================
    // HELPER — Formato de salida estándar
    // ================================================================
    private function format(TicketArea $area): array
    {
        return [
            'id'          => $area->id,
            'nombre'      => $area->nombre,
            'descripcion' => $area->descripcion,
            'activo'      => $area->activo,
            'deleted_at'  => $area->deleted_at?->format('Y-m-d H:i:s'),
            'responsable' => $area->responsable ? [
                'id'     => $area->responsable->id,
                'nombre' => trim($area->responsable->name . ' ' . ($area->responsable->surname ?? '')),
                'avatar' => $area->responsable->avatar,
            ] : null,
            'created_at'  => $area->created_at?->format('Y-m-d H:i:s'),
            'updated_at'  => $area->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}