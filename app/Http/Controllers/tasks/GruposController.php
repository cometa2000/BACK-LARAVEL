<?php

namespace App\Http\Controllers\tasks;

use App\Models\User;
use App\Models\tasks\Grupos;
use Illuminate\Http\Request;
use App\Mail\GrupoCreadoMail;
use App\Models\tasks\Workspace;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use App\Mail\GrupoCompartidoInvitadoMail;
use App\Mail\GrupoCompartidoPropietarioMail;
use Illuminate\Validation\ValidationException;

class GruposController extends Controller
{

    public function index(Request $request)
    {
        try {
            Log::info('=== INICIO GruposController::index ===');
            
            $user = auth()->user();
            $search = $request->get('search', '');
            $page = $request->get('page', 1);
            
            Log::info('Usuario autenticado', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'search' => $search,
                'page' => $page
            ]);
            
            // ✅ SOLUCIÓN SIMPLIFICADA: Obtener todos los grupos y ordenar en memoria
            $gruposQuery = Grupos::where(function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->orWhereHas('sharedUsers', function($q) use ($user) {
                              $q->where('users.id', $user->id);
                          });
                })
                ->when($search, function($query) use ($search) {
                    return $query->where('name', 'like', '%' . $search . '%');
                })
                ->with(['user', 'sharedUsers', 'workspace', 'listas', 'favoritedByUsers'])
                ->orderBy('created_at', 'desc')
                ->get();

            // ✅ Ordenar en memoria: primero favoritos, luego por fecha
            $gruposOrdenados = $gruposQuery->sortByDesc(function($grupo) use ($user) {
                return $grupo->isFavoritedBy($user->id) ? 1 : 0;
            })->values();

            // ✅ Paginar manualmente
            $page = $request->get('page', 1);
            $perPage = 10;
            $total = $gruposOrdenados->count();
            $grupos = new \Illuminate\Pagination\LengthAwarePaginator(
                $gruposOrdenados->forPage($page, $perPage),
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            Log::info('Grupos encontrados', [
                'total' => $grupos->total(),
                'per_page' => $grupos->perPage(),
                'current_page' => $grupos->currentPage()
            ]);

            // Agregar información de permisos a cada grupo
            $grupos->getCollection()->transform(function ($grupo) use ($user) {
                $isOwner = $grupo->user_id == $user->id;
                $permissionLevel = $grupo->getUserPermissionLevel($user->id);
                
                $grupo->is_owner = $isOwner;
                $grupo->has_write_access = $grupo->hasWriteAccess($user->id);
                $grupo->permission_level = $permissionLevel;
                
                // ✅ Agregar user_permission para compatibilidad con frontend
                $grupo->user_permission = $permissionLevel;
                
                // ⭐ NUEVO: Verificar si está marcado como favorito por el usuario actual
                $grupo->is_starred = $grupo->isFavoritedBy($user->id);
                
                // ✅ Agregar shared_with
                $grupo->shared_with = $grupo->sharedUsers;
                
                Log::info('Grupo procesado', [
                    'id' => $grupo->id,
                    'name' => $grupo->name,
                    'is_owner' => $isOwner,
                    'user_permission' => $permissionLevel,
                    'has_write_access' => $grupo->has_write_access,
                    'owner_id' => $grupo->user_id,
                    'current_user_id' => $user->id,
                    'is_starred' => $grupo->is_starred,
                    'shared_with_count' => $grupo->sharedUsers->count()
                ]);
                
                return $grupo;
            });

            Log::info('=== FIN GruposController::index (éxito) ===');

            return response()->json([
                'message' => 200,
                'grupos' => $grupos
            ]);

        } catch (\Exception $e) {
            Log::error('ERROR en GruposController::index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 500,
                'message_text' => 'Error al obtener grupos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('=== INICIO store GruposController ===');
            Log::info('Datos recibidos:', $request->all());
            
            $request->validate([
                'name' => 'required|string|max:250',
                'color' => 'nullable|string|max:20',
                'image' => 'nullable', // Puede ser archivo o string
                'workspace_id' => 'nullable|exists:workspaces,id',
            ]);

            $user = auth()->user();
            Log::info('Usuario autenticado:', ['id' => $user->id, 'name' => $user->name]);
            
            // 🆕 Si no se proporciona workspace_id, el grupo quedará sin workspace
            $workspaceId = $request->workspace_id;

            Log::info('Workspace asignado:', ['workspace_id' => $workspaceId]);

            // ✅ Manejar imagen: puede ser archivo subido O nombre de fondo predeterminado
            $imagePath = null;
            
            if ($request->hasFile('image')) {
                // Caso 1: Se subió un archivo
                Log::info('Procesando archivo subido...');
                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $imagePath = $image->storeAs('grupos', $imageName, 'public');
                Log::info('Archivo guardado:', ['path' => $imagePath]);
            } elseif ($request->has('image') && is_string($request->image)) {
                // Caso 2: Es un nombre de fondo predeterminado (ej: "fondo1.png")
                Log::info('Usando fondo predeterminado:', ['image' => $request->image]);
                $imagePath = $request->image; // Guardar el nombre del fondo
            }

            Log::info('Image path final:', ['image' => $imagePath]);

            // Crear grupo
            $grupo = Grupos::create([
                'name' => $request->name,
                'color' => $request->color ?? '#6366f1',
                'image' => $imagePath,
                'user_id' => $user->id,
                'workspace_id' => $workspaceId,
                'is_starred' => false,
                'permission_type' => 'all',
            ]);

            Log::info('Grupo creado:', ['id' => $grupo->id, 'name' => $grupo->name]);

            // Cargar relaciones
            $grupo->load(['user', 'workspace']);

            // Agregar información de permisos
            $grupo->is_owner = true;
            $grupo->has_write_access = true;
            $grupo->permission_level = 'owner';

            Log::info('=== FIN store GruposController (éxito) ===');

            return response()->json([
                'message' => 200,
                'message_text' => 'Grupo creado exitosamente',
                'grupo' => $grupo
            ]);

        } catch (\Exception $e) {
            Log::error('ERROR en store GruposController:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 500,
                'message_text' => 'Error al crear grupo: ' . $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }
    

    public function show($id)
    {
        try {
            $user = auth()->user();
            
            $grupo = Grupos::where(function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->orWhereHas('sharedUsers', function($q) use ($user) {
                              $q->where('users.id', $user->id);
                          });
                })
                ->with(['user', 'sharedUsers', 'workspace', 'listas'])
                ->findOrFail($id);

            // Agregar información de permisos
            $grupo->is_owner = $grupo->user_id == $user->id;
            $grupo->has_write_access = $grupo->hasWriteAccess($user->id);
            $grupo->permission_level = $grupo->getUserPermissionLevel($user->id);
            $grupo->user_permission = $grupo->getUserPermissionLevel($user->id);
            
            // ⭐ NUEVO: Verificar si está marcado como favorito por el usuario actual
            $grupo->is_starred = $grupo->isFavoritedBy($user->id);

            // ✅ Procesar imagen
            if ($grupo->image) {
                if (preg_match('/^fondo\d+\.png$/', $grupo->image)) {
                    $grupo->imagen = asset('assets/media/fondos/' . $grupo->image);
                } elseif (str_starts_with($grupo->image, 'grupos/')) {
                    $grupo->imagen = url('storage/' . $grupo->image);
                } else {
                    $grupo->imagen = $grupo->image;
                }
            } else {
                $grupo->imagen = null;
            }

            // ✅ Agregar shared_with
            $grupo->shared_with = $grupo->sharedUsers;

            return response()->json([
                'message' => 200,
                'grupo' => $grupo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 404,
                'message_text' => 'Grupo no encontrado o sin acceso'
            ], 404);
        }
    }

    /**
     * ✅ SOLUCIÓN PROBLEMA 2: Update mejorado para manejar fondos predeterminados
     */
    public function update(Request $request, $id)
    {
        try {
            Log::info('=== INICIO update GruposController ===');
            Log::info('Datos recibidos:', $request->all());
            Log::info('Grupo ID:', ['id' => $id]);
            
            // ✅ Validación mejorada: image puede ser archivo O string
            $request->validate([
                'name' => 'required|string|max:250',
                'color' => 'nullable|string|max:20',
                'image' => 'nullable', // ✅ Quitar validación estricta de archivo
                'workspace_id' => 'nullable|exists:workspaces,id',
            ]);

            $user = auth()->user();
            $grupo = Grupos::findOrFail($id);

            Log::info('Grupo encontrado:', [
                'id' => $grupo->id,
                'name' => $grupo->name,
                'user_id' => $grupo->user_id,
                'current_image' => $grupo->image
            ]);

            // Verificar que el usuario tenga permiso para editar
            if (!$grupo->hasWriteAccess($user->id)) {
                Log::warning('Usuario sin permisos de escritura', [
                    'user_id' => $user->id,
                    'grupo_id' => $id
                ]);
                
                return response()->json([
                    'message' => 403,
                    'message_text' => 'No tienes permiso para editar este grupo'
                ], 403);
            }

            // ✅ Manejar imagen: archivo O nombre de fondo predeterminado
            if ($request->hasFile('image')) {
                Log::info('Procesando archivo de imagen...');
                
                // Eliminar imagen anterior si NO es un fondo predeterminado
                if ($grupo->image && !preg_match('/^fondo\d+\.png$/', $grupo->image)) {
                    Storage::disk('public')->delete($grupo->image);
                    Log::info('Imagen anterior eliminada:', ['image' => $grupo->image]);
                }
                
                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $imagePath = $image->storeAs('grupos', $imageName, 'public');
                $grupo->image = $imagePath;
                
                Log::info('Nueva imagen guardada:', ['path' => $imagePath]);
                
            } elseif ($request->has('image') && is_string($request->image)) {
                // ✅ Es un nombre de fondo predeterminado
                Log::info('Actualizando con fondo predeterminado:', ['image' => $request->image]);
                
                // Si la imagen anterior NO era un fondo predeterminado, eliminarla
                if ($grupo->image && 
                    !preg_match('/^fondo\d+\.png$/', $grupo->image) && 
                    str_starts_with($grupo->image, 'grupos/')) {
                    Storage::disk('public')->delete($grupo->image);
                    Log::info('Imagen personalizada anterior eliminada');
                }
                
                $grupo->image = $request->image;
                Log::info('Fondo predeterminado asignado:', ['image' => $request->image]);
            }

            // Actualizar campos básicos
            $grupo->name = $request->name;
            
            if ($request->has('color')) {
                $grupo->color = $request->color;
            }
            
            // 🆕 Solo el propietario puede cambiar el workspace
            if ($grupo->user_id === $user->id && $request->has('workspace_id')) {
                $grupo->workspace_id = $request->workspace_id;
                Log::info('Workspace actualizado:', ['workspace_id' => $request->workspace_id]);
            }
            
            $grupo->save();

            Log::info('Grupo actualizado exitosamente:', [
                'id' => $grupo->id,
                'name' => $grupo->name,
                'image' => $grupo->image
            ]);

            // Recargar relaciones
            $grupo->load(['user', 'sharedUsers', 'workspace']);

            // Agregar información de permisos
            $grupo->is_owner = $grupo->user_id == $user->id;
            $grupo->has_write_access = $grupo->hasWriteAccess($user->id);
            $grupo->permission_level = $grupo->getUserPermissionLevel($user->id);
            $grupo->user_permission = $grupo->getUserPermissionLevel($user->id);
            $grupo->shared_with = $grupo->sharedUsers;

            Log::info('=== FIN update GruposController (éxito) ===');

            return response()->json([
                'message' => 200,
                'message_text' => 'Grupo actualizado exitosamente',
                'grupo' => $grupo
            ]);

        } catch (\Exception $e) {
            Log::error('ERROR en update GruposController:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 500,
                'message_text' => 'Error al actualizar grupo: ' . $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = auth()->user();
            $grupo = Grupos::findOrFail($id);

            // Solo el propietario puede eliminar
            if ($grupo->user_id !== $user->id) {
                return response()->json([
                    'message' => 403,
                    'message_text' => 'Solo el propietario puede eliminar este grupo'
                ], 403);
            }

            // Eliminar imagen si existe y no es un fondo predeterminado
            if ($grupo->image && !preg_match('/^fondo\d+\.png$/', $grupo->image)) {
                Storage::disk('public')->delete($grupo->image);
            }

            $grupo->delete();

            return response()->json([
                'message' => 200,
                'message_text' => 'Grupo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 500,
                'message_text' => 'Error al eliminar grupo: ' . $e->getMessage()
            ], 500);
        }
    }

    // Toggle estrella (favorito)
    /**
     * ⭐ Marcar/Desmarcar grupo como favorito (por usuario)
     * POST /api/grupos/{id}/toggle-star
     */
    public function toggleStar($id)
    {
        try {
            $user = auth()->user();
            
            // Verificar que el usuario tenga acceso al grupo
            $grupo = Grupos::where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereHas('sharedUsers', function($q) use ($user) {
                          $q->where('users.id', $user->id);
                      });
            })->findOrFail($id);
            
            // ⭐ NUEVO: Usar sistema de favoritos por usuario
            $isStarred = $grupo->isFavoritedBy($user->id);
            
            if ($isStarred) {
                $grupo->removeFavorite($user->id);
                $newStatus = false;
                $message = 'Grupo desmarcado como favorito';
            } else {
                $grupo->addFavorite($user->id);
                $newStatus = true;
                $message = 'Grupo marcado como favorito';
            }

            Log::info('Toggle star ejecutado', [
                'grupo_id' => $grupo->id,
                'user_id' => $user->id,
                'is_starred' => $newStatus
            ]);

            return response()->json([
                'message' => 200,
                'is_starred' => $newStatus,
                'message_text' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Error en toggleStar', [
                'error' => $e->getMessage(),
                'grupo_id' => $id,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'message' => 500,
                'message_text' => 'Error al marcar grupo: ' . $e->getMessage()
            ], 500);
        }
    }

    // Compartir grupo con usuarios
    public function share(Request $request, $id)
    {
        try {
            $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
            ]);

            $user = auth()->user();
            $grupo = Grupos::where('user_id', $user->id)->findOrFail($id);

            // Obtener IDs de usuarios ya compartidos
            $existingUserIds = $grupo->sharedUsers()->pluck('users.id')->toArray();
            
            // Filtrar solo nuevos usuarios
            $newUserIds = array_diff($request->user_ids, $existingUserIds);
            
            if (count($newUserIds) > 0) {
                // Agregar nuevos usuarios con nivel de permiso 'write' por defecto
                $syncData = [];
                foreach ($newUserIds as $userId) {
                    $syncData[$userId] = ['permission_level' => 'write'];
                }
                
                $grupo->sharedUsers()->attach($syncData);
                
                Log::info('✅ Usuarios agregados al grupo', [
                    'grupo_id' => $grupo->id,
                    'grupo_nombre' => $grupo->name,
                    'propietario_id' => $user->id,
                    'nuevos_usuarios' => $newUserIds,
                    'total_nuevos' => count($newUserIds)
                ]);
                
                // 📝 NOTA: Sistema de notificaciones deshabilitado temporalmente
                // Para habilitar notificaciones, descomentar el código siguiente y
                // asegurarse de que NotificationService::grupoCompartido() existe
                
                /*
                // ✅ CREAR NOTIFICACIONES para cada usuario nuevo
                try {
                    $propietario = $user;
                    
                    foreach ($newUserIds as $userId) {
                        $invitado = User::find($userId);
                        
                        if ($invitado && class_exists('App\Services\NotificationService')) {
                            NotificationService::grupoCompartido(
                                $grupo,
                                $propietario,
                                $invitado
                            );
                            
                            Log::info('✅ Notificación de grupo compartido enviada', [
                                'grupo_id' => $grupo->id,
                                'propietario_id' => $propietario->id,
                                'invitado_id' => $userId
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('⚠️ No se pudieron crear algunas notificaciones: ' . $e->getMessage());
                }
                */
            }

            // Recargar usuarios compartidos
            $grupo->load('sharedUsers');

            return response()->json([
                'message' => 200,
                'message_text' => 'Grupo compartido exitosamente',
                'shared_users' => $grupo->sharedUsers,
                'new_users_count' => count($newUserIds)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 500,
                'message_text' => 'Error al compartir grupo: ' . $e->getMessage()
            ], 500);
        }
    }

    // Dejar de compartir con un usuario
    public function unshare($grupoId, $userId)
    {
        try {
            $user = auth()->user();
            $grupo = Grupos::where('user_id', $user->id)->findOrFail($grupoId);

            $grupo->sharedUsers()->detach($userId);

            return response()->json([
                'message' => 200,
                'message_text' => 'Usuario removido del grupo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 500,
                'message_text' => 'Error al dejar de compartir: ' . $e->getMessage()
            ], 500);
        }
    }

    // Buscar usuarios para compartir
    public function searchUsers(Request $request)
    {
        try {
            $search = $request->get('search', '');
            $currentUserId = auth()->id();

            $users = User::where('id', '!=', $currentUserId)
                ->when($search, function($query) use ($search) {
                    return $query->where(function($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%')
                          ->orWhere('email', 'like', '%' . $search . '%');
                    });
                })
                ->select('id', 'name', 'email', 'avatar')
                ->limit(10)
                ->get();

            return response()->json([
                'message' => 200,
                'users' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 500,
                'message_text' => 'Error al buscar usuarios: ' . $e->getMessage()
            ], 500);
        }
    }

    // Obtener usuarios con quienes está compartido
    public function getSharedUsers($id)
    {
        try {
            $user = auth()->user();
            
            $grupo = Grupos::where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereHas('sharedUsers', function($q) use ($user) {
                          $q->where('users.id', $user->id);
                      });
            })->with('sharedUsers')->findOrFail($id);

            return response()->json([
                'message' => 200,
                'shared_users' => $grupo->sharedUsers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 500,
                'message_text' => 'Error al obtener usuarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener configuración de permisos del grupo
     * GET /api/grupos/{id}/permissions
     */
    /**
     * Obtener permisos del grupo
     * GET /api/grupos/{id}/permissions
     */
    public function getPermissions($id)
    {
        try {
            $grupo = Grupos::with('sharedUsers')->findOrFail($id);
            
            // Verificar que el usuario sea el propietario
            if ($grupo->user_id != auth()->id()) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "Solo el propietario puede ver los permisos"
                ], 403);
            }

            // ✅ CORREGIDO: Retornar estructura correcta con permissions anidado
            return response()->json([
                "message" => 200,
                "permissions" => [
                    "permission_type" => $grupo->permission_type,
                    "users" => $grupo->sharedUsers->map(function($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'surname' => $user->surname ?? '',
                            'email' => $user->email,
                            'avatar' => $user->avatar,
                            'permission_level' => $user->pivot->permission_level
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener permisos: ' . $e->getMessage());
            
            return response()->json([
                "message" => 500,
                "message_text" => "Error al obtener permisos"
            ], 500);
        }
    }

    /**
     * Actualizar el tipo de permiso general del grupo
     * POST /api/grupos/{id}/permissions/type
     * Body: { permission_type: 'all' | 'readonly' | 'custom' }
     */
    public function updatePermissionType(Request $request, $id)
    {
        try {
            $request->validate([
                'permission_type' => 'required|in:all,readonly,custom'
            ]);

            $grupo = Grupos::findOrFail($id);
            
            // Verificar que el usuario sea el propietario
            if ($grupo->user_id != auth()->id()) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "Solo el propietario puede cambiar los permisos"
                ], 403);
            }

            $grupo->permission_type = $request->permission_type;
            $grupo->save();

            Log::info('Tipo de permiso actualizado', [
                'grupo_id' => $id,
                'permission_type' => $request->permission_type,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                "message" => 200,
                "message_text" => "Permisos actualizados correctamente",
                "permission_type" => $grupo->permission_type
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 422,
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al actualizar tipo de permiso: ' . $e->getMessage());
            
            return response()->json([
                "message" => 500,
                "message_text" => "Error al actualizar permisos"
            ], 500);
        }
    }

    /**
     * Actualizar nivel de permiso de un usuario específico
     * POST /api/grupos/{grupoId}/permissions/user/{userId}
     * Body: { permission_level: 'read' | 'write' }
     */
    public function updateUserPermission(Request $request, $grupoId, $userId)
    {
        try {
            $request->validate([
                'permission_level' => 'required|in:read,write'
            ]);

            $grupo = Grupos::findOrFail($grupoId);
            
            // Verificar que el usuario autenticado sea el propietario
            if ($grupo->user_id != auth()->id()) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "Solo el propietario puede cambiar permisos de usuarios"
                ], 403);
            }

            // Verificar que el usuario esté compartido en el grupo
            if (!$grupo->sharedUsers()->where('user_id', $userId)->exists()) {
                return response()->json([
                    "message" => 404,
                    "message_text" => "El usuario no tiene acceso a este grupo"
                ], 404);
            }

            // Actualizar el nivel de permiso en la tabla pivote
            $grupo->sharedUsers()->updateExistingPivot($userId, [
                'permission_level' => $request->permission_level
            ]);

            Log::info('Permiso de usuario actualizado', [
                'grupo_id' => $grupoId,
                'user_id' => $userId,
                'permission_level' => $request->permission_level,
                'updated_by' => auth()->id()
            ]);
            
            // 📝 NOTA: Sistema de notificaciones deshabilitado temporalmente
            /*
            // ✅ CREAR NOTIFICACIÓN EN EL SISTEMA para el usuario afectado
            try {
                $propietario = auth()->user();
                $afectado = User::find($userId);
                
                if ($afectado && class_exists('App\Services\NotificationService')) {
                    NotificationService::permisosCambiados(
                        $grupo,
                        $propietario,
                        $afectado,
                        $request->permission_level
                    );
                    
                    Log::info('✅ Notificación de cambio de permisos enviada', [
                        'grupo_id' => $grupo->id,
                        'user_id' => $userId,
                        'new_permission' => $request->permission_level
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ No se pudo crear notificación de cambio de permisos: ' . $e->getMessage());
            }
            */

            return response()->json([
                "message" => 200,
                "message_text" => "Permiso actualizado correctamente",
                "permission_level" => $request->permission_level
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 422,
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al actualizar permiso de usuario: ' . $e->getMessage());
            
            return response()->json([
                "message" => 500,
                "message_text" => "Error al actualizar permiso"
            ], 500);
        }
    }

    /**
     * Verificar si el usuario autenticado tiene permisos de escritura en el grupo
     * GET /api/grupos/{id}/check-write-access
     */
    public function checkWriteAccess($id)
    {
        try {
            $grupo = Grupos::findOrFail($id);
            $userId = auth()->id();
            
            $hasWriteAccess = $grupo->hasWriteAccess($userId);
            $permissionLevel = $grupo->getUserPermissionLevel($userId);
            $isOwner = $grupo->isOwner($userId);

            return response()->json([
                "message" => 200,
                "has_write_access" => $hasWriteAccess,
                "permission_level" => $permissionLevel,
                "is_owner" => $isOwner,
                "permission_type" => $grupo->permission_type
            ]);

        } catch (\Exception $e) {
            Log::error('Error al verificar permisos: ' . $e->getMessage());
            
            return response()->json([
                "message" => 500,
                "message_text" => "Error al verificar permisos"
            ], 500);
        }
    }

    /**
     * Actualizar permisos de múltiples usuarios a la vez
     * POST /api/grupos/{id}/permissions/batch
     * Body: { users: [{ user_id: 1, permission_level: 'read' }, ...] }
     */
    public function updateBatchPermissions(Request $request, $id)
    {
        try {
            $request->validate([
                'users' => 'required|array',
                'users.*.user_id' => 'required|exists:users,id',
                'users.*.permission_level' => 'required|in:read,write'
            ]);

            $grupo = Grupos::findOrFail($id);
            
            // Verificar que el usuario autenticado sea el propietario
            if ($grupo->user_id != auth()->id()) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "Solo el propietario puede cambiar permisos"
                ], 403);
            }

            $updated = 0;
            foreach ($request->users as $userData) {
                if ($grupo->sharedUsers()->where('user_id', $userData['user_id'])->exists()) {
                    $grupo->sharedUsers()->updateExistingPivot($userData['user_id'], [
                        'permission_level' => $userData['permission_level']
                    ]);
                    $updated++;
                }
            }

            Log::info('Permisos actualizados en lote', [
                'grupo_id' => $id,
                'users_updated' => $updated,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                "message" => 200,
                "message_text" => "Permisos actualizados correctamente",
                "users_updated" => $updated
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 422,
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al actualizar permisos en lote: ' . $e->getMessage());
            
            return response()->json([
                "message" => 500,
                "message_text" => "Error al actualizar permisos"
            ], 500);
        }
    }

    /**
     * 🆕 Mover grupo a otro workspace
     * POST /api/grupos/{id}/move
     * Body: { workspace_id: 1 (o null para sin workspace) }
     */
    public function moveToWorkspace(Request $request, $id)
    {
        try {
            $request->validate([
                'workspace_id' => 'nullable|exists:workspaces,id'
            ]);

            $user = auth()->user();
            $grupo = Grupos::where('user_id', $user->id)->findOrFail($id);

            // Si workspace_id no es null, verificar que pertenezca al usuario
            if ($request->workspace_id !== null) {
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

            Log::info('Grupo movido a workspace', [
                'grupo_id' => $id,
                'workspace_id' => $request->workspace_id,
                'moved_by' => $user->id
            ]);

            return response()->json([
                'message' => 200,
                'message_text' => 'Grupo movido exitosamente',
                'grupo' => $grupo->load('workspace')
            ]);

        } catch (\Exception $e) {
            Log::error('Error al mover grupo: ' . $e->getMessage());
            
            return response()->json([
                'message' => 500,
                'message_text' => 'Error al mover grupo: ' . $e->getMessage()
            ], 500);
        }
    }
}