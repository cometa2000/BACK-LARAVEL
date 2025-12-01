<?php

namespace App\Http\Controllers\tasks;

use App\Models\User;
use App\Models\tasks\Grupos;
use Illuminate\Http\Request;
use App\Mail\GrupoCreadoMail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Services\NotificationService;
use App\Mail\GrupoCompartidoInvitadoMail;
use App\Mail\GrupoCompartidoPropietarioMail;
use Illuminate\Validation\ValidationException;

class GruposController extends Controller
{

    public function index(Request $request)
    {
        $search = $request->get("search");
        $userId = auth()->id();

        // Obtener grupos propios y compartidos
        $grupos = Grupos::accessibleBy($userId)
            ->where('name', 'like', "%{$search}%")
            ->orderBy('is_starred', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25);

        return response()->json([
            "total" => $grupos->total(),
            "grupos" => $grupos->map(function($grupo) use ($userId) {
                return [
                    "id" => $grupo->id,
                    "name" => $grupo->name,
                    "color" => $grupo->color,
                    "image" => $grupo->image,
                    "user_id" => $grupo->user_id,
                    "user" => $grupo->user,
                    "is_starred" => $grupo->is_starred,
                    "is_owner" => $grupo->user_id == $userId,
                    "permission_type" => $grupo->permission_type,
                    "has_write_access" => $grupo->hasWriteAccess($userId),
                    "permission_level" => $grupo->getUserPermissionLevel($userId),
                    "shared_with" => $grupo->sharedUsers->map(function($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'surname' => $user->surname,
                            'permission_level' => $user->pivot->permission_level
                        ];
                    }),
                    "created_at" => $grupo->created_at->format("Y-m-d h:i A")
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        try {
            // ✅ Validación
            $request->validate([
                'name' => 'required|string|max:250|unique:grupos,name,NULL,id,deleted_at,NULL'
            ]);

            // ✅ Crear grupo con permisos por defecto
            $request->merge([
                'user_id' => auth()->id(),
                'permission_type' => 'all' // Permisos completos por defecto
            ]);
            
            $grupo = Grupos::create($request->all());

            // ✅ Cargar relaciones necesarias
            $grupo->load('sharedUsers', 'user');

            // ✅ ENVIAR CORREO ELECTRÓNICO
            try {
                $usuario = auth()->user();
                
                // Verificar que el usuario tenga email
                if ($usuario && $usuario->email) {
                    Mail::to($usuario->email)
                        ->send(new GrupoCreadoMail(
                            $grupo->name,
                            trim($usuario->name . ' ' . ($usuario->surname ?? ''))
                        ));
                    
                    Log::info('✅ Correo enviado a: ' . $usuario->email);
                }
                
            } catch (\Exception $e) {
                Log::warning('⚠️ No se pudo enviar correo: ' . $e->getMessage());
            }

            try {
                NotificationService::grupoCreado($grupo, $usuario);
                
                Log::info('✅ Notificación de grupo creado enviada', [
                    'grupo_id' => $grupo->id,
                    'grupo_nombre' => $grupo->name,
                    'usuario_id' => $usuario->id
                ]);
            } catch (\Exception $e) {
                Log::warning('⚠️ No se pudo crear notificación de grupo creado: ' . $e->getMessage());
            }

            // ✅ Devolver la MISMA estructura que index()
            return response()->json([
                "message" => 200,
                "grupo" => [
                    "id" => $grupo->id,
                    "name" => $grupo->name,
                    "color" => $grupo->color,
                    "image" => $grupo->image,
                    "user_id" => $grupo->user_id,
                    "user" => $grupo->user,
                    "is_starred" => $grupo->is_starred ?? false,
                    "is_owner" => true,
                    "permission_type" => $grupo->permission_type,
                    "has_write_access" => true,
                    "permission_level" => 'owner',
                    "shared_with" => [],
                    "created_at" => $grupo->created_at->format("Y-m-d h:i A")
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                "message" => 403,
                "message_text" => "El nombre del grupo ya existe o es inválido",
                "errors" => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ Error al crear grupo: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                "message" => 500,
                "message_text" => "Error interno al crear el grupo",
                "error" => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function show(string $id)
    {
        $grupo = Grupos::with(['sharedUsers', 'user'])->findOrFail($id);

        return response()->json([
            'message' => 200,
            'grupo' => [
                'id' => $grupo->id,
                'name' => $grupo->name,
                'image' => $grupo->image,
                'color' => $grupo->color,
                'is_starred' => $grupo->is_starred,
                'user_id' => $grupo->user_id,
                'permission_type' => $grupo->permission_type,
                'has_write_access' => $grupo->hasWriteAccess(auth()->id()),
                'permission_level' => $grupo->getUserPermissionLevel(auth()->id()),
                'created_at' => $grupo->created_at ? $grupo->created_at->format('Y-m-d h:i A') : null,
                'sharedUsers' => $grupo->sharedUsers->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'surname' => $user->surname,
                        'email' => $user->email,
                        'avatar' => $user->avatar ? env("APP_URL")."/storage/".$user->avatar : null,
                        'permission_level' => $user->pivot->permission_level
                    ];
                }),
                'user' => [
                    'id' => $grupo->user->id,
                    'name' => $grupo->user->name,
                    'surname' => $grupo->user->surname,
                    'email' => $grupo->user->email,
                    'avatar' => $grupo->user->avatar ? env("APP_URL")."/storage/".$grupo->user->avatar : null,
                ],
            ],
        ]);
    }

    public function update(Request $request, string $id)
    {
        try {
            $is_exits_grupo = Grupos::where("name", $request->name)
                                ->where("id", "<>", $id)
                                ->whereNull('deleted_at')
                                ->first();
            
            if ($is_exits_grupo) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "El nombre del grupo ya existe"
                ], 422);
            }
            
            $grupo = Grupos::findOrFail($id);
            $grupo->update($request->all());
            
            // ✅ Cargar relaciones
            $grupo->load('sharedUsers');
            
            return response()->json([
                "message" => 200,
                "grupo" => [
                    "id" => $grupo->id,
                    "name" => $grupo->name,
                    "color" => $grupo->color,
                    "image" => $grupo->image,
                    "user_id" => $grupo->user_id,
                    "user" => $grupo->user,
                    "is_starred" => $grupo->is_starred,
                    "is_owner" => $grupo->user_id == auth()->id(),
                    "permission_type" => $grupo->permission_type,
                    "has_write_access" => $grupo->hasWriteAccess(auth()->id()),
                    "permission_level" => $grupo->getUserPermissionLevel(auth()->id()),
                    "shared_with" => $grupo->sharedUsers->map(function($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'surname' => $user->surname,
                            'permission_level' => $user->pivot->permission_level
                        ];
                    }),
                    "created_at" => $grupo->created_at->format("Y-m-d h:i A")
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error al actualizar grupo: ' . $e->getMessage());
            
            return response()->json([
                "message" => 500,
                "message_text" => "Error al actualizar el grupo"
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $grupo = Grupos::findOrFail($id);
            
            // Verificar que el usuario sea el propietario
            if ($grupo->user_id != auth()->id()) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "No tienes permiso para eliminar este grupo"
                ], 403);
            }
            
            $grupo->delete();
            return response()->json(["message" => 200]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar grupo: ' . $e->getMessage());
            
            return response()->json([
                "message" => 500,
                "message_text" => "Error al eliminar el grupo"
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
                        ? env("APP_URL")."/storage/".$user->avatar 
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
                        ? env("APP_URL")."/storage/".$user->avatar 
                        : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png',
                    'permission_level' => $user->pivot->permission_level
                ];
            })
        ]);
    }

    // ========================================
    // 🔒 MÉTODOS DE PERMISOS
    // ========================================

    /**
     * Obtener configuración de permisos del grupo
     * GET /api/grupos/{id}/permissions
     */
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
                        ? env("APP_URL")."/storage/".$user->avatar 
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

    /**
     * Actualizar tipo de permiso general del grupo
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