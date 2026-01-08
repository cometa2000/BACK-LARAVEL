<?php

namespace App\Http\Controllers\tasks;

use Illuminate\Http\Request;
use App\Models\tasks\Workspace;
use App\Models\tasks\Grupos;
use App\Models\tasks\Tareas;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WorkspaceController extends Controller
{
    /**
     * 📋 Listar todos los workspaces del usuario
     * ✅ ACTUALIZADO: Sin workspaces "General" automáticos
     */
    public function index(Request $request)
    {
        try {
            Log::info('=== INICIO WorkspaceController::index ===');
            
            $user = auth()->user();
            
            if (!$user) {
                Log::error('Usuario no autenticado');
                return response()->json([
                    'message' => 401,
                    'message_text' => 'No autenticado'
                ], 401);
            }
            
            Log::info('Usuario autenticado', ['id' => $user->id, 'name' => $user->name]);
            
            $search = $request->get('search', '');
            
            // ✅ Obtener workspaces del usuario
            Log::info('Buscando workspaces del usuario...');
            
            $workspaces = Workspace::where('user_id', $user->id)
                ->when($search, function($query) use ($search) {
                    return $query->where('name', 'like', '%' . $search . '%');
                })
                ->with(['grupos' => function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->with(['user', 'sharedUsers'])
                        ->orderBy('is_starred', 'desc')
                        ->orderBy('created_at', 'desc');
                }])
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info('Workspaces encontrados', ['count' => $workspaces->count()]);

            // ✅ Procesar cada workspace
            $workspaces->each(function ($workspace) use ($user) {
                if ($workspace->grupos) {
                    $workspace->grupos->transform(function ($grupo) use ($user) {
                        $grupo->is_owner = $grupo->user_id == $user->id;
                        $grupo->has_write_access = $grupo->hasWriteAccess($user->id);
                        $grupo->permission_level = $grupo->getUserPermissionLevel($user->id);
                        
                        // ✅ Procesar imagen del grupo
                        $grupo->image = $this->processGrupoImage($grupo->image);
                        
                        // ✅ Agregar shared_with
                        $grupo->shared_with = $grupo->sharedUsers;
                        
                        return $grupo;
                    });
                }
            });

            Log::info('=== FIN WorkspaceController::index ===');

            return response()->json([
                'message' => 200,
                'workspaces' => $workspaces
            ]);

        } catch (\Exception $e) {
            Log::error('Error en WorkspaceController::index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 500,
                'message_text' => 'Error al cargar workspaces: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🎨 Procesar imagen del grupo
     */
    private function processGrupoImage($image)
    {
        if (!$image) {
            return null;
        }

        // Si es un nombre de fondo predeterminado (ej: "fondo1.png")
        if (preg_match('/^fondo\d+\.png$/', $image)) {
            return $image;
        } 
        
        // Si es una ruta de storage
        if (str_starts_with($image, 'grupos/')) {
            return url('storage/' . $image);
        } 
        
        return $image;
    }

    /**
     * 📄 Obtener workspace específico con sus grupos
     */
    public function show($id)
    {
        try {
            $user = auth()->user();

            $workspace = Workspace::where('id', $id)
                ->where('user_id', $user->id)
                ->with(['grupos' => function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->with(['user', 'sharedUsers'])
                        ->orderBy('is_starred', 'desc')
                        ->orderBy('created_at', 'desc');
                }])
                ->first();

            if (!$workspace) {
                return response()->json([
                    'message' => 404,
                    'message_text' => 'Workspace no encontrado'
                ], 404);
            }

            // ✅ Procesar grupos
            if ($workspace->grupos) {
                $workspace->grupos->transform(function ($grupo) use ($user) {
                    $grupo->is_owner = $grupo->user_id == $user->id;
                    $grupo->has_write_access = $grupo->hasWriteAccess($user->id);
                    $grupo->permission_level = $grupo->getUserPermissionLevel($user->id);
                    $grupo->image = $this->processGrupoImage($grupo->image);
                    $grupo->shared_with = $grupo->sharedUsers;
                    
                    return $grupo;
                });
            }

            return response()->json([
                'message' => 200,
                'workspace' => $workspace
            ]);

        } catch (\Exception $e) {
            Log::error('Error en WorkspaceController::show', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 500,
                'message_text' => 'Error al cargar workspace: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✏️ Crear nuevo workspace
     */
    public function store(Request $request)
    {
        try {
            Log::info('=== INICIO WorkspaceController::store ===');
            Log::info('Datos recibidos:', $request->all());

            // Validar datos
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'description' => 'nullable|string|max:500',
                'color' => 'required|string|max:20'
            ]);

            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'message' => 401,
                    'message_text' => 'Usuario no autenticado'
                ], 401);
            }

            Log::info('Creando workspace para usuario', ['user_id' => $user->id]);

            // Crear workspace SIN campos is_general e is_shared
            $workspace = Workspace::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'color' => $validated['color'],
                'user_id' => $user->id,
            ]);

            Log::info('Workspace creado exitosamente', [
                'id' => $workspace->id,
                'name' => $workspace->name
            ]);

            return response()->json([
                'message' => 200,
                'message_text' => 'Workspace creado exitosamente',
                'workspace' => $workspace
            ]);

        } catch (ValidationException $e) {
            Log::error('Error de validación', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'message' => 422,
                'message_text' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al crear workspace', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 500,
                'message_text' => 'Error al crear workspace: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔄 Actualizar workspace
     */
    public function update(Request $request, $id)
    {
        try {
            Log::info('=== INICIO WorkspaceController::update ===');
            Log::info('ID:', ['id' => $id]);
            Log::info('Datos recibidos:', $request->all());

            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'message' => 401,
                    'message_text' => 'Usuario no autenticado'
                ], 401);
            }

            // Buscar workspace
            $workspace = Workspace::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$workspace) {
                return response()->json([
                    'message' => 404,
                    'message_text' => 'Workspace no encontrado'
                ], 404);
            }

            // Validar datos
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'description' => 'nullable|string|max:500',
                'color' => 'required|string|max:20'
            ]);

            // Actualizar workspace
            $workspace->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'color' => $validated['color'],
            ]);

            Log::info('Workspace actualizado', ['id' => $workspace->id]);

            return response()->json([
                'message' => 200,
                'message_text' => 'Workspace actualizado exitosamente',
                'workspace' => $workspace
            ]);

        } catch (ValidationException $e) {
            Log::error('Error de validación', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'message' => 422,
                'message_text' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al actualizar workspace', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 500,
                'message_text' => 'Error al actualizar workspace: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🗑️ Eliminar workspace
     */
    public function destroy($id)
    {
        try {
            Log::info('=== INICIO WorkspaceController::destroy ===');
            Log::info('ID:', ['id' => $id]);

            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'message' => 401,
                    'message_text' => 'Usuario no autenticado'
                ], 401);
            }

            $workspace = Workspace::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$workspace) {
                return response()->json([
                    'message' => 404,
                    'message_text' => 'Workspace no encontrado'
                ], 404);
            }

            // ✅ Mover grupos a null (quedarán sin workspace)
            $gruposMovidos = Grupos::where('workspace_id', $workspace->id)
                ->update(['workspace_id' => null]);

            Log::info('Grupos movidos a null', ['count' => $gruposMovidos]);

            // Eliminar workspace
            $workspace->delete();

            Log::info('Workspace eliminado', ['id' => $id]);

            return response()->json([
                'message' => 200,
                'message_text' => 'Workspace eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar workspace', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 500,
                'message_text' => 'Error al eliminar workspace: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📂 Obtener grupos de un workspace
     */
    public function getWorkspaceGroups($workspaceId)
    {
        try {
            $user = auth()->user();
            $search = request()->get('search', '');

            $workspace = Workspace::where('id', $workspaceId)
                ->where('user_id', $user->id)
                ->first();

            if (!$workspace) {
                return response()->json([
                    'message' => 404,
                    'message_text' => 'Workspace no encontrado'
                ], 404);
            }

            $grupos = Grupos::where('workspace_id', $workspaceId)
                ->where('user_id', $user->id)
                ->when($search, function($query) use ($search) {
                    return $query->where('name', 'like', '%' . $search . '%');
                })
                ->with(['user', 'sharedUsers'])
                ->orderBy('is_starred', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            // ✅ Procesar grupos
            $grupos->transform(function ($grupo) use ($user) {
                $grupo->is_owner = $grupo->user_id == $user->id;
                $grupo->has_write_access = $grupo->hasWriteAccess($user->id);
                $grupo->permission_level = $grupo->getUserPermissionLevel($user->id);
                $grupo->image = $this->processGrupoImage($grupo->image);
                $grupo->shared_with = $grupo->sharedUsers;
                
                return $grupo;
            });

            return response()->json([
                'message' => 200,
                'grupos' => $grupos,
                'total' => $grupos->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getWorkspaceGroups', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 500,
                'message_text' => 'Error al cargar grupos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📊 Obtener estadísticas del workspace
     */
    public function getStats($id)
    {
        try {
            $user = auth()->user();

            $workspace = Workspace::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$workspace) {
                return response()->json([
                    'message' => 404,
                    'message_text' => 'Workspace no encontrado'
                ], 404);
            }

            $stats = $workspace->getStats();

            return response()->json([
                'message' => 200,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getStats', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 500,
                'message_text' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔄 Mover grupo a otro workspace
     */
    public function moveGroup(Request $request, $grupoId)
    {
        try {
            $user = auth()->user();

            $grupo = Grupos::where('id', $grupoId)
                ->where('user_id', $user->id)
                ->first();

            if (!$grupo) {
                return response()->json([
                    'message' => 404,
                    'message_text' => 'Grupo no encontrado'
                ], 404);
            }

            $request->validate([
                'workspace_id' => 'nullable|exists:workspaces,id'
            ]);

            // Si workspace_id es null, permitir (mover a "sin workspace")
            if ($request->workspace_id !== null) {
                // Verificar que el workspace pertenezca al usuario
                $workspace = Workspace::where('id', $request->workspace_id)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$workspace) {
                    return response()->json([
                        'message' => 403,
                        'message_text' => 'No tienes permiso para mover a este workspace'
                    ], 403);
                }
            }

            $grupo->workspace_id = $request->workspace_id;
            $grupo->save();

            Log::info('Grupo movido', [
                'grupo_id' => $grupoId,
                'workspace_id' => $request->workspace_id
            ]);

            return response()->json([
                'message' => 200,
                'message_text' => 'Grupo movido exitosamente',
                'grupo' => $grupo
            ]);

        } catch (\Exception $e) {
            Log::error('Error al mover grupo', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 500,
                'message_text' => 'Error al mover grupo: ' . $e->getMessage()
            ], 500);
        }
    }
} 