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
            $user = auth()->user();
            $search = $request->get('search', '');
            $page = $request->get('page', 1);
            
            // Obtener grupos propios y compartidos
            $grupos = Grupos::where(function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->orWhereHas('sharedUsers', function($q) use ($user) {
                              $q->where('users.id', $user->id);
                          });
                })
                ->when($search, function($query) use ($search) {
                    return $query->where('name', 'like', '%' . $search . '%');
                })
                ->with(['user', 'sharedUsers', 'workspace', 'listas'])
                ->orderBy('is_starred', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            // Agregar información de permisos a cada grupo
            $grupos->getCollection()->transform(function ($grupo) use ($user) {
                $grupo->is_owner = $grupo->user_id == $user->id;
                $grupo->has_write_access = $grupo->hasWriteAccess($user->id);
                $grupo->permission_level = $grupo->getUserPermissionLevel($user->id);
                return $grupo;
            });

            return response()->json([
                'message' => 200,
                'grupos' => $grupos
            ]);

        } catch (\Exception $e) {
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
            
            // 🆕 Si no se proporciona workspace_id, obtener o crear workspace General
            $workspaceId = $request->workspace_id;
            
            if (!$workspaceId) {
                Log::info('No se proporcionó workspace_id, buscando/creando General...');
                
                $generalWorkspace = Workspace::where('user_id', $user->id)
                    ->where('is_general', true)
                    ->first();
                
                if (!$generalWorkspace) {
                    Log::info('Creando workspace General...');
                    
                    $generalWorkspace = Workspace::create([
                        'name' => 'General',
                        'description' => 'Espacio de trabajo principal',
                        'color' => '#3b82f6',
                        'user_id' => $user->id,
                        'is_general' => true,
                        'is_shared' => false,
                    ]);
                    
                    Log::info('Workspace General creado:', ['id' => $generalWorkspace->id]);
                } else {
                    Log::info('Workspace General ya existe:', ['id' => $generalWorkspace->id]);
                }
                
                $workspaceId = $generalWorkspace->id;
            }

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

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:250',
                'color' => 'nullable|string|max:20',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'workspace_id' => 'nullable|exists:workspaces,id',
            ]);

            $user = auth()->user();
            $grupo = Grupos::findOrFail($id);

            // Verificar que el usuario tenga permiso para editar
            if (!$grupo->hasWriteAccess($user->id)) {
                return response()->json([
                    'message' => 403,
                    'message_text' => 'No tienes permiso para editar este grupo'
                ], 403);
            }

            // Manejar imagen si se proporciona
            if ($request->hasFile('image')) {
                // Eliminar imagen anterior si existe
                if ($grupo->image) {
                    Storage::disk('public')->delete($grupo->image);
                }
                
                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $imagePath = $image->storeAs('grupos', $imageName, 'public');
                $grupo->image = $imagePath;
            }

            // Actualizar campos básicos
            $grupo->name = $request->name;
            $grupo->color = $request->color ?? $grupo->color;
            
            // 🆕 Solo el propietario puede cambiar el workspace
            if ($grupo->user_id === $user->id && $request->has('workspace_id')) {
                $grupo->workspace_id = $request->workspace_id;
            }
            
            $grupo->save();

            // Recargar relaciones
            $grupo->load(['user', 'sharedUsers', 'workspace']);

            // Agregar información de permisos
            $grupo->is_owner = $grupo->user_id == $user->id;
            $grupo->has_write_access = $grupo->hasWriteAccess($user->id);
            $grupo->permission_level = $grupo->getUserPermissionLevel($user->id);

            return response()->json([
                'message' => 200,
                'message_text' => 'Grupo actualizado exitosamente',
                'grupo' => $grupo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 500,
                'message_text' => 'Error al actualizar grupo: ' . $e->getMessage()
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

            // Eliminar imagen si existe
            if ($grupo->image) {
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

    public function moveToWorkspace(Request $request, $id)
    {
        try {
            $request->validate([
                'workspace_id' => 'required|exists:workspaces,id',
            ]);

            $user = auth()->user();
            $grupo = Grupos::findOrFail($id);

            // Solo el propietario puede mover el grupo
            if ($grupo->user_id !== $user->id) {
                return response()->json([
                    'message' => 403,
                    'message_text' => 'Solo el propietario puede mover este grupo'
                ], 403);
            }

            // Verificar que el workspace pertenezca al usuario
            $workspace = Workspace::where('user_id', $user->id)
                ->findOrFail($request->workspace_id);

            $grupo->workspace_id = $workspace->id;
            $grupo->save();

            // Recargar con workspace
            $grupo->load('workspace');

            return response()->json([
                'message' => 200,
                'message_text' => 'Grupo movido exitosamente',
                'grupo' => $grupo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 500,
                'message_text' => 'Error al mover grupo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleStar($id)
    {
        $grupo = Grupos::findOrFail($id);
        
        if ($grupo->user_id != auth()->id() && !$grupo->sharedUsers->contains(auth()->id())) {
            return response()->json([
                "message" => 403,
                "message_text" => "No tienes acceso a este grupo"
            ], 403);
        }

        $grupo->is_starred = !$grupo->is_starred;
        $grupo->save();

        return response()->json([
            "message" => 200,
            "is_starred" => $grupo->is_starred
        ]);
    }

    public function share(Request $request, $id)
    {
        try {
            // Validar los datos
            $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id'
            ]);

            // Buscar el grupo
            $grupo = Grupos::findOrFail($id);
            
            // Verificar que el usuario autenticado sea el propietario
            if ($grupo->user_id != auth()->id()) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "Solo el propietario puede compartir este grupo"
                ], 403);
            }

            // Obtener el propietario del grupo
            $propietario = auth()->user();
            $nombrePropietario = trim($propietario->name . ' ' . ($propietario->surname ?? ''));

            // Obtener los usuarios que van a ser agregados (solo los nuevos)
            $usuariosExistentes = $grupo->sharedUsers->pluck('id')->toArray();
            $nuevosUsuariosIds = array_diff($request->user_ids, $usuariosExistentes);
            
            // Si no hay usuarios nuevos para agregar
            if (empty($nuevosUsuariosIds)) {
                return response()->json([
                    "message" => 200,
                    "message_text" => "Los usuarios ya están agregados al grupo",
                    "shared_with" => $grupo->sharedUsers->map(function($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'permission_level' => $user->pivot->permission_level
                        ];
                    })
                ]);
            }

            // Obtener los datos de los nuevos usuarios
            $nuevosUsuarios = User::whereIn('id', $nuevosUsuariosIds)->get();

            // Compartir el grupo (agregar usuarios con permiso 'write' por defecto)
            foreach ($nuevosUsuariosIds as $userId) {
                $grupo->sharedUsers()->attach($userId, ['permission_level' => 'write']);
            }

            // ✅ ENVIAR CORREOS ELECTRÓNICOS
            
            // 1️⃣ Preparar datos de usuarios para el correo del propietario
            $usuariosParaCorreo = $nuevosUsuarios->map(function($user) {
                return [
                    'name' => trim($user->name . ' ' . ($user->surname ?? '')),
                    'email' => $user->email
                ];
            })->toArray();

            // 2️⃣ Enviar correo al PROPIETARIO (quien comparte)
            try {
                if ($propietario && $propietario->email) {
                    Mail::to($propietario->email)
                        ->send(new GrupoCompartidoPropietarioMail(
                            $grupo->name,
                            $nombrePropietario,
                            $usuariosParaCorreo
                        ));
                    
                    // ✅ CREAR NOTIFICACIÓN EN EL SISTEMA para el propietario
                    NotificationService::grupoCompartidoPropietario(
                        $grupo, 
                        $propietario, 
                        $usuariosParaCorreo
                    );
                    
                    Log::info('✅ Correo y notificación enviados al propietario', [
                        'email' => $propietario->email,
                        'grupo_id' => $grupo->id,
                        'usuarios_compartidos' => count($usuariosParaCorreo)
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('❌ Error al enviar correo/notificación al propietario: ' . $e->getMessage());
            }

            // 3️⃣ Enviar correo a CADA USUARIO INVITADO
            foreach ($nuevosUsuarios as $usuario) {
                try {
                    if ($usuario && $usuario->email) {
                        $nombreInvitado = trim($usuario->name . ' ' . ($usuario->surname ?? ''));
                        
                        // Enviar correo
                        Mail::to($usuario->email)
                            ->send(new GrupoCompartidoInvitadoMail(
                                $grupo->name,
                                $nombreInvitado,
                                $nombrePropietario
                            ));
                        
                        // ✅ CREAR NOTIFICACIÓN EN EL SISTEMA para el invitado
                        NotificationService::grupoCompartidoInvitado(
                            $grupo, 
                            $propietario, 
                            $usuario
                        );
                        
                        Log::info('✅ Correo y notificación enviados al invitado', [
                            'email' => $usuario->email,
                            'grupo_id' => $grupo->id,
                            'nombre' => $nombreInvitado
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Error al enviar correo/notificación al invitado ' . $usuario->email . ': ' . $e->getMessage());
                }
            }

            // Recargar la relación para obtener los usuarios actualizados
            $grupo->load('sharedUsers');

            return response()->json([
                "message" => 200,
                "message_text" => "Grupo compartido exitosamente y correos enviados",
                "shared_with" => $grupo->sharedUsers->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'permission_level' => $user->pivot->permission_level
                    ];
                })
            ]);

        } catch (ValidationException $e) {
            Log::warning('Validación fallida al compartir grupo', [
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'message' => 422,
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al compartir grupo: ' . $e->getMessage());
            
            return response()->json([
                "message" => 500,
                "message_text" => "Error al compartir el grupo"
            ], 500);
        }
    }

    public function searchUsers(Request $request)
    {
        $search = $request->get('search', '');
        
        if (empty(trim($search)) || strlen(trim($search)) < 2) {
            return response()->json([
                'message' => 200,
                'users' => []
            ]);
        }
        
        $users = User::where('id', '!=', auth()->id())
            ->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('surname', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            })
            ->select('id', 'name', 'surname', 'email', 'phone', 'avatar')
            ->orderBy('name', 'asc')
            ->limit(10)
            ->get();

        return response()->json([
            'message' => 200,
            'users' => $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => trim($user->name . ' ' . ($user->surname ?? '')),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar 
                        ? $user->avatar 
                        : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'
                ];
            })
        ]);
    }

    public function unshare($grupoId, $userId)
    {
        $grupo = Grupos::findOrFail($grupoId);
        
        if ($grupo->user_id != auth()->id()) {
            return response()->json([
                "message" => 403,
                "message_text" => "Solo el propietario puede dejar de compartir"
            ], 403);
        }

        $grupo->sharedUsers()->detach($userId);

        return response()->json(["message" => 200]);
    }

    public function getSharedUsers($id)
    {
        $grupo = Grupos::findOrFail($id);
        
        if ($grupo->user_id != auth()->id() && !$grupo->sharedUsers->contains(auth()->id())) {
            return response()->json([
                "message" => 403,
                "message_text" => "No tienes acceso a este grupo"
            ], 403);
        }

        return response()->json([
            "message" => 200,
            "shared_users" => $grupo->sharedUsers->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => trim($user->name . ' ' . ($user->surname ?? '')),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar 
                        ? $user->avatar 
                        : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png',
                    'permission_level' => $user->pivot->permission_level
                ];
            })
        ]);
    }

    // ========================================
    // 🔒 MÉTODOS DE PERMISOS
    // ========================================
    public function getPermissions($id)
    {
        try {
            $grupo = Grupos::with('sharedUsers')->findOrFail($id);
            
            // Verificar que el usuario autenticado sea el propietario
            if ($grupo->user_id != auth()->id()) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "Solo el propietario puede ver los permisos"
                ], 403);
            }

            // ✅ CORRECCIÓN: Cambiar 'shared_users' a 'users' y separar name/surname
            $sharedUsersWithPermissions = $grupo->sharedUsers->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,           // ✅ Separado
                    'surname' => $user->surname,     // ✅ Separado
                    'email' => $user->email,
                    'avatar' => $user->avatar 
                        ? $user->avatar 
                        : null,  // ✅ Usar null en vez de imagen placeholder
                    'permission_level' => $user->pivot->permission_level
                ];
            });

            return response()->json([
                "message" => 200,
                "permissions" => [
                    'permission_type' => $grupo->permission_type,
                    'users' => $sharedUsersWithPermissions  // ✅ Cambiar a 'users'
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

    public function updatePermissionType(Request $request, $id)
    {
        try {
            $request->validate([
                'permission_type' => 'required|in:all,readonly,custom'
            ]);

            $grupo = Grupos::findOrFail($id);
            
            // Verificar que el usuario autenticado sea el propietario
            if ($grupo->user_id != auth()->id()) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "Solo el propietario puede cambiar los permisos"
                ], 403);
            }

            $oldType = $grupo->permission_type;
            $grupo->permission_type = $request->permission_type;
            $grupo->save();

            Log::info('Tipo de permiso actualizado', [
                'grupo_id' => $grupo->id,
                'old_type' => $oldType,
                'new_type' => $grupo->permission_type,
                'user_id' => auth()->id()
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

            return response()->json([
                "message" => 200,
                "message_text" => "Permiso actualizado correctamente",
                "permission_level" => $request->permission_level
            ]);

            // Actualizar el nivel de permiso en la tabla pivote
            $grupo->sharedUsers()->updateExistingPivot($userId, [
                'permission_level' => $request->permission_level
            ]);

            // ✅ CREAR NOTIFICACIÓN EN EL SISTEMA para el usuario afectado
            try {
                $propietario = auth()->user();
                $afectado = User::find($userId);
                
                if ($afectado) {
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

            Log::info('Permiso de usuario actualizado', [
                'grupo_id' => $grupoId,
                'user_id' => $userId,
                'permission_level' => $request->permission_level,
                'updated_by' => auth()->id()
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
}