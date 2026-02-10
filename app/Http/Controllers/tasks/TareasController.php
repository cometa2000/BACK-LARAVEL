<?php

namespace App\Http\Controllers\tasks;

use App\Models\tasks\Tareas;
use Illuminate\Http\Request;
use App\Models\tasks\Timeline;
use App\Mail\TareaAsignadaMail;
use App\Models\tasks\Actividad;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Services\NotificationService; 
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TareasController extends Controller
{
    /**
     * MÉTODO INDEX
     * Listar todas las tareas con sus relaciones
     */
    public function index(Request $request)
    {
        try {
            Log::info('TareasController@index - Iniciando', [
                'request' => $request->all()
            ]);

            // Construcción de la consulta base
            $query = Tareas::with([
                'etiquetas',
                'checklists.items',
                'user',
                'lista',
                'grupo',
                'assignedUsers'
            ]);

            // Filtros opcionales
            if ($request->has('lista_id')) {
                $query->where('lista_id', $request->lista_id);
            }

            if ($request->has('grupo_id')) {
                $query->where('grupo_id', $request->grupo_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Ordenar por orden
            $query->orderBy('orden', 'asc');

            // Obtener tareas
            $tareas = $query->get();

            // Mapear las tareas con formato completo
            $tareasFormateadas = $tareas->map(function($tarea) {
                return [
                    'id' => $tarea->id,
                    'name' => $tarea->name,
                    'description' => $tarea->description,
                    'start_date' => $tarea->start_date,
                    'due_date' => $tarea->due_date,
                    'status' => $tarea->status,
                    'priority' => $tarea->priority,
                    'orden' => $tarea->orden,
                    
                    // Relaciones
                    'etiquetas' => $tarea->etiquetas,
                    'checklists' => $tarea->checklists->map(function($checklist) {
                        return [
                            'id' => $checklist->id,
                            'name' => $checklist->name,
                            'progress' => $checklist->progress,
                            'items_count' => $checklist->items->count(),
                            'completed_items' => $checklist->items->where('completed', true)->count()
                        ];
                    }),

                    // AGREGADO: Miembros asignados
                    'assigned_members' => $tarea->assignedUsers->map(function($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'surname' => $user->surname,
                            'email' => $user->email,
                            'avatar' => $user->avatar ? $user->avatar : null,
                        ];
                    }),
                    
                    // Indicadores útiles
                    'is_overdue' => $tarea->isOverdue(),
                    'is_due_soon' => $tarea->isDueSoon(),
                    'total_checklist_progress' => $tarea->getTotalChecklistProgress(),
                    
                    // Otras propiedades
                    'user' => $tarea->user,
                    'lista' => $tarea->lista,
                    'grupo' => $tarea->grupo,
                    'created_at' => $tarea->created_at ? $tarea->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $tarea->updated_at ? $tarea->updated_at->format('Y-m-d H:i:s') : null,
                ];
            });

            Log::info('TareasController@index - Tareas obtenidas', [
                'count' => $tareasFormateadas->count()
            ]);

            return response()->json([
                'message' => 200,
                'tareas' => $tareasFormateadas
            ]);

        } catch (\Exception $e) {
            Log::error('TareasController@index - Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 500,
                'error' => 'Error al obtener las tareas'
            ], 500);
        }
    }

    /**
     * MÉTODO SHOW
     * Mostrar una tarea específica con todas sus relaciones
     */
    public function show($id)
    {
        try {
            Log::info('TareasController@show - Iniciando', ['tarea_id' => $id]);

            // ✅ CORRECCIÓN CRÍTICA: Cargar checklists.items.assignedUsers
            $tarea = Tareas::with([
                'etiquetas',
                'checklists.items.assignedUsers',  // ✅ CAMBIO AQUÍ: Agregado .assignedUsers
                'comentarios.user',
                'actividades.user',
                'user',
                'lista',
                'grupo',
                'assignedUsers'
            ])->findOrFail($id);

            Log::info('TareasController@show - Tarea encontrada', [
                'tarea_id' => $tarea->id,
                'tarea_name' => $tarea->name,
                'checklists_count' => $tarea->checklists->count()
            ]);

            // ✅ DEBUG: Verificar que assignedUsers se cargó
            if ($tarea->checklists->isNotEmpty() && $tarea->checklists->first()->items->isNotEmpty()) {
                $firstItem = $tarea->checklists->first()->items->first();
                Log::info('DEBUG - Primer item del primer checklist:', [
                    'item_id' => $firstItem->id,
                    'item_name' => $firstItem->name,
                    'assigned_users_count' => $firstItem->assignedUsers->count(),
                    'assigned_users' => $firstItem->assignedUsers->toArray()
                ]);
            }

            // Cargar adjuntos de forma separada y segura
            $enlaces = [];
            $archivos = [];

            try {
                // Obtener adjuntos desde la tabla tarea_adjuntos
                $adjuntos = \App\Models\tasks\TareaAdjunto::where('tarea_id', $id)->get();
                
                Log::info('Adjuntos encontrados', [
                    'count' => $adjuntos->count(),
                    'adjuntos' => $adjuntos->toArray()
                ]);

                foreach ($adjuntos as $adjunto) {
                    if ($adjunto->tipo === 'enlace') {
                        $enlaces[] = [
                            'id' => $adjunto->id,
                            'url' => $adjunto->url,
                            'nombre' => $adjunto->nombre,
                        ];
                    } elseif ($adjunto->tipo === 'archivo') {
                        $archivos[] = [
                            'id' => $adjunto->id,
                            'nombre' => $adjunto->nombre,
                            'tipo' => $adjunto->mime_type,
                            'tiempo_subida' => $adjunto->created_at->toISOString(),
                            'preview' => $adjunto->preview,
                            'file_url' => $adjunto->file_path ? url('storage/' . $adjunto->file_path) : null
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error al cargar adjuntos', [
                    'error' => $e->getMessage(),
                    'tarea_id' => $id
                ]);
                // Continuar sin adjuntos si hay error
            }

            // ✅ CORRECCIÓN CRÍTICA: Formatear checklists con assigned_users
            $checklistsFormateados = $tarea->checklists->map(function($checklist) {
                return [
                    'id' => $checklist->id,
                    'name' => $checklist->name,
                    'orden' => $checklist->orden,
                    'progress' => $checklist->progress,
                    'items' => $checklist->items->map(function($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'completed' => $item->completed,
                            'orden' => $item->orden,
                            'due_date' => $item->due_date ? $item->due_date->format('Y-m-d') : null,
                            'assigned_users' => $item->assignedUsers->map(function($user) {
                                return [
                                    'id' => $user->id,
                                    'name' => $user->name,
                                    'surname' => $user->surname,
                                    'email' => $user->email,
                                    'avatar' => $user->avatar ? $user->avatar : null
                                ];
                            }),
                            'is_overdue' => $item->isOverdue(),
                            'is_due_soon' => $item->isDueSoon()
                        ];
                    })
                ];
            });

            // Construir respuesta
            $tareaData = [
                'id' => $tarea->id,
                'name' => $tarea->name,
                'description' => $tarea->description,
                'type_task' => $tarea->type_task,
                'priority' => $tarea->priority,
                'start_date' => $tarea->start_date,
                'due_date' => $tarea->due_date,
                'status' => $tarea->status,
                'orden' => $tarea->orden,
                'grupo_id' => $tarea->grupo_id,
                'lista_id' => $tarea->lista_id,
                'user_id' => $tarea->user_id,
                
                // Relaciones
                'etiquetas' => $tarea->etiquetas,
                'checklists' => $checklistsFormateados,  // ✅ CAMBIO AQUÍ: Usar versión formateada
                'comentarios' => $tarea->comentarios,
                'user' => $tarea->user,
                'lista' => $tarea->lista,
                'grupo' => $tarea->grupo,

                // Miembros asignados
                'assigned_members' => $tarea->assignedUsers->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'surname' => $user->surname,
                        'email' => $user->email,
                        'avatar' => $user->avatar ? $user->avatar : null,
                    ];
                }),
                
                // ADJUNTOS procesados
                'adjuntos' => [
                    'enlaces' => $enlaces,
                    'archivos' => $archivos
                ],
                
                // Indicadores
                'is_overdue' => $tarea->isOverdue(),
                'is_due_soon' => $tarea->isDueSoon(),
                'total_checklist_progress' => $tarea->getTotalChecklistProgress(),
                'total_checklist_items' => $tarea->getTotalChecklistItems(),
                'completed_checklist_items' => $tarea->getCompletedChecklistItems(),
                
                'created_at' => $tarea->created_at,
                'updated_at' => $tarea->updated_at,
            ];

            Log::info('TareasController@show - Respuesta preparada', [
                'tarea_id' => $tarea->id,
                'checklists_count' => $checklistsFormateados->count()
            ]);

            return response()->json([
                'message' => 200,
                'tarea' => $tareaData
            ]);

        } catch (ModelNotFoundException $e) {
            Log::error('TareasController@show - Tarea no encontrada', [
                'tarea_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 404,
                'error' => 'Tarea no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error('TareasController@show - Error inesperado', [
                'tarea_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 500,
                'error' => 'Error al cargar la tarea'
            ], 500);
        }
    }

    /**
     * MÉTODO STORE
     * Crear una nueva tarea
     */
    public function store(Request $request)
    {
        try {
            Log::info('TareasController@store - Iniciando', [
                'data' => $request->all()
            ]);

            $request->validate([
                'name' => 'required|string|max:150',
                'description' => 'nullable|string',
                'type_task' => 'required|in:simple,evento',
                'priority' => 'nullable|in:low,medium,high',
                'start_date' => 'nullable|date',
                'due_date' => 'nullable|date|after_or_equal:start_date',
                'status' => 'nullable|in:pendiente,en_progreso,completada',
                'lista_id' => 'required|exists:listas,id', // ✅ REQUERIDO
            ]);

            // ✅ CRÍTICO: Obtener grupo_id desde la lista
            $listaId = $request->lista_id;
            $lista = \App\Models\tasks\Lista::findOrFail($listaId);
            $grupoId = $lista->grupo_id;

            if (!$grupoId) {
                Log::warning('TareasController@store - Lista sin grupo_id', [
                    'lista_id' => $listaId,
                    'lista_name' => $lista->name
                ]);
            }

            Log::info('✅ grupo_id obtenido desde lista', [
                'lista_id' => $listaId,
                'lista_name' => $lista->name,
                'grupo_id' => $grupoId
            ]);

            // Obtener el máximo orden actual
            $maxOrden = Tareas::where('lista_id', $listaId)->max('orden') ?? -1;

            // ✅ CREAR TAREA CON grupo_id
            $tarea = Tareas::create([
                'name' => $request->name,
                'description' => $request->description,
                'type_task' => $request->type_task,
                'priority' => $request->priority ?? 'medium',
                'start_date' => $request->start_date,
                'due_date' => $request->due_date,
                'status' => $request->status ?? 'pendiente',
                'lista_id' => $listaId,
                'grupo_id' => $grupoId, // ✅ INCLUIR GRUPO_ID
                'orden' => $maxOrden + 1,
                'user_id' => auth()->id(),
                'notifications_enabled' => $request->notifications_enabled ?? false,
                'notification_days_before' => $request->notification_days_before ?? null,
            ]);

            // Registrar actividad
            Actividad::create([
                'type' => 'created',
                'description' => 'creó la tarea',
                'tarea_id' => $tarea->id,
                'user_id' => auth()->id()
            ]);

            Log::info('TareasController@store - Tarea creada exitosamente', [
                'tarea_id' => $tarea->id,
                'tarea_name' => $tarea->name,
                'lista_id' => $listaId,
                'grupo_id' => $grupoId // ✅ CONFIRMAR
            ]);

            return response()->json([
                'message' => 200,
                'tarea' => $tarea->fresh(['lista', 'grupo', 'user'])
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('TareasController@store - Validación fallida', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'message' => 422,
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('TareasController@store - Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 500,
                'error' => 'Error al crear la tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * MÉTODO UPDATE - CON NOTIFICACIONES DE TAREA COMPLETADA
     * Actualizar una tarea existente (con soporte para notificaciones)
     */
    public function update(Request $request, $id)
    {
        try {
            Log::info('TareasController@update - Iniciando', [
                'tarea_id' => $id,
                'data' => $request->all()
            ]);

            $tarea = Tareas::findOrFail($id);

            $request->validate([
                'name' => 'sometimes|required|string|max:150',
                'description' => 'nullable|string',
                'type_task' => 'sometimes|in:simple,evento',
                'priority' => 'nullable|in:low,medium,high',
                'start_date' => 'nullable|date',
                'due_date' => 'nullable|date',
                'status' => 'nullable|in:pendiente,en_progreso,completada',
                'lista_id' => 'sometimes|exists:listas,id',
                'notifications_enabled' => 'nullable|boolean',
                'notification_days_before' => 'nullable|integer|min:1|max:30',
            ]);

            $dataToUpdate = $request->only([
                'name', 'description', 'type_task', 'priority',
                'start_date', 'due_date', 'status', 'lista_id',
                'notifications_enabled', 'notification_days_before'
            ]);

            // ✅ CRÍTICO: Si cambia lista_id, actualizar grupo_id también
            if (isset($dataToUpdate['lista_id']) && $dataToUpdate['lista_id'] != $tarea->lista_id) {
                $nuevaLista = \App\Models\tasks\Lista::findOrFail($dataToUpdate['lista_id']);
                $dataToUpdate['grupo_id'] = $nuevaLista->grupo_id;
                
                Log::info('✅ grupo_id actualizado por cambio de lista', [
                    'tarea_id' => $id,
                    'lista_id_anterior' => $tarea->lista_id,
                    'lista_id_nueva' => $dataToUpdate['lista_id'],
                    'grupo_id_anterior' => $tarea->grupo_id,
                    'grupo_id_nuevo' => $dataToUpdate['grupo_id']
                ]);

                // Registrar actividad del cambio
                Actividad::create([
                    'type' => 'moved',
                    'description' => 'movió la tarea a otra lista',
                    'tarea_id' => $tarea->id,
                    'user_id' => auth()->id()
                ]);
            }

            $tarea->update($dataToUpdate);

            Log::info('TareasController@update - Tarea actualizada exitosamente', [
                'tarea_id' => $id,
                'grupo_id' => $tarea->grupo_id
            ]);

            return response()->json([
                'message' => 200,
                'tarea' => $tarea->fresh(['lista', 'grupo', 'user'])
            ]);

        } catch (ModelNotFoundException $e) {
            Log::error('TareasController@update - Tarea no encontrada', [
                'tarea_id' => $id
            ]);

            return response()->json([
                'message' => 404,
                'error' => 'Tarea no encontrada'
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('TareasController@update - Validación fallida', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'message' => 422,
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('TareasController@update - Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 500,
                'error' => 'Error al actualizar la tarea: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * MÉTODO DESTROY
     * Eliminar una tarea (soft delete)
     */
    public function destroy($id)
    {
        try {
            Log::info('TareasController@destroy - Iniciando', [
                'tarea_id' => $id
            ]);

            $tarea = Tareas::findOrFail($id);
            $tarea->delete();

            Log::info('TareasController@destroy - Tarea eliminada', [
                'tarea_id' => $id
            ]);

            return response()->json([
                'message' => 200,
                'success' => 'Tarea eliminada correctamente'
            ]);

        } catch (ModelNotFoundException $e) {
            Log::warning('TareasController@destroy - Tarea no encontrada', [
                'tarea_id' => $id
            ]);

            return response()->json([
                'message' => 404,
                'error' => 'Tarea no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error('TareasController@destroy - Error', [
                'tarea_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 500,
                'error' => 'Error al eliminar la tarea'
            ], 500);
        }
    }

    /**
     * MÉTODO MOVE
     * Mover tarea a otra lista
     */
    public function move(Request $request, $id)
    {
        try {
            Log::info('TareasController@move - Iniciando', [
                'tarea_id' => $id,
                'nueva_lista_id' => $request->lista_id
            ]);

            $tarea = Tareas::findOrFail($id);
            $oldListaId = $tarea->lista_id;

            $tarea->lista_id = $request->lista_id;
            $tarea->save();

            // Registrar actividad
            Actividad::create([
                'type' => 'moved',
                'description' => 'movió la tarea a otra lista',
                'tarea_id' => $tarea->id,
                'user_id' => auth()->id(),
                'changes' => json_encode([
                    'old_lista_id' => $oldListaId,
                    'new_lista_id' => $request->lista_id
                ])
            ]);

            Log::info('TareasController@move - Tarea movida', [
                'tarea_id' => $id,
                'old_lista_id' => $oldListaId,
                'new_lista_id' => $request->lista_id
            ]);

            return response()->json([
                'message' => 200,
                'tarea' => $tarea
            ]);

        } catch (ModelNotFoundException $e) {
            Log::warning('TareasController@move - Tarea no encontrada', [
                'tarea_id' => $id
            ]);

            return response()->json([
                'message' => 404,
                'error' => 'Tarea no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error('TareasController@move - Error', [
                'tarea_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 500,
                'error' => 'Error al mover la tarea'
            ], 500);
        }
    }

    /**
     * MÉTODO CONFIG
     * Obtener configuración para formularios
     */
    public function config()
    {
        try {
            Log::info('TareasController@config - Iniciando');

            $config = [
                'priorities' => [
                    ['value' => 'low', 'label' => 'Baja'],
                    ['value' => 'medium', 'label' => 'Media'],
                    ['value' => 'high', 'label' => 'Alta'],
                ],
                'statuses' => [
                    ['value' => 'pendiente', 'label' => 'Pendiente'],
                    ['value' => 'en_progreso', 'label' => 'En Progreso'],
                    ['value' => 'completada', 'label' => 'Completada'],
                ],
                'types' => [
                    ['value' => 'simple', 'label' => 'Simple'],
                    ['value' => 'evento', 'label' => 'Evento'],
                ],
            ];

            return response()->json([
                'message' => 200,
                'config' => $config
            ]);

        } catch (\Exception $e) {
            Log::error('TareasController@config - Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 500,
                'error' => 'Error al obtener configuración'
            ], 500);
        }
    }

    /**
     * MÉTODO ESTADÍSTICAS
     * Obtener estadísticas de una tarea
     */
    public function estadisticas($id)
    {
        try {
            Log::info('TareasController@estadisticas - Iniciando', [
                'tarea_id' => $id
            ]);

            $tarea = Tareas::with(['etiquetas', 'checklists.items'])->findOrFail($id);

            $estadisticas = [
                'etiquetas_count' => $tarea->etiquetas->count(),
                'checklists_count' => $tarea->checklists->count(),
                'total_checklist_items' => $tarea->getTotalChecklistItems(),
                'completed_items' => $tarea->getCompletedChecklistItems(),
                'total_progress' => $tarea->getTotalChecklistProgress(),
                'is_overdue' => $tarea->isOverdue(),
                'is_due_soon' => $tarea->isDueSoon(),
            ];

            return response()->json([
                'message' => 200,
                'estadisticas' => $estadisticas
            ]);

        } catch (ModelNotFoundException $e) {
            Log::warning('TareasController@estadisticas - Tarea no encontrada', [
                'tarea_id' => $id
            ]);

            return response()->json([
                'message' => 404,
                'error' => 'Tarea no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error('TareasController@estadisticas - Error', [
                'tarea_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 500,
                'error' => 'Error al obtener estadísticas'
            ], 500);
        }
    }

    /**
     * POST /api/tareas/{tareaId}/assign-members
     * Body: { user_ids: [1, 2, 3] }
     */
    public function assignMembers(Request $request, $tareaId)
    {
        try {
            Log::info('TareasController@assignMembers - Iniciando', [
                'tarea_id' => $tareaId,
                'user_ids' => $request->user_ids
            ]);

            $tarea = Tareas::findOrFail($tareaId);
            
            // Validar que el usuario tenga permiso (es el creador o miembro del grupo)
            $grupo = $tarea->lista->grupo;
            if ($grupo->user_id !== auth()->id() && 
                !$grupo->sharedUsers->contains(auth()->id())) {
                return response()->json([
                    'message' => 403,
                    'message_text' => 'No tienes permiso para asignar miembros a esta tarea'
                ], 403);
            }
            
            // Validar que los usuarios pertenezcan al grupo
            $validUserIds = collect($request->user_ids)->filter(function($userId) use ($grupo) {
                return $grupo->sharedUsers->contains($userId) || $grupo->user_id == $userId;
            });
            
            if ($validUserIds->isEmpty()) {
                return response()->json([
                    'message' => 400,
                    'message_text' => 'Los usuarios seleccionados no pertenecen al grupo'
                ], 400);
            }

            // Obtener IDs de usuarios ya asignados
            $usuariosYaAsignados = $tarea->assignedUsers->pluck('id')->toArray();
            
            // Filtrar solo los nuevos usuarios (que no estaban asignados antes)
            $nuevosUsuarios = $validUserIds->filter(function($userId) use ($usuariosYaAsignados) {
                return !in_array($userId, $usuariosYaAsignados);
            });
            
            // Asignar miembros (sin duplicar)
            $tarea->assignedUsers()->syncWithoutDetaching($validUserIds->toArray());

            // 📧 ENVIAR CORREOS SOLO A LOS NUEVOS USUARIOS ASIGNADOS
            if ($nuevosUsuarios->isNotEmpty()) {
                $asignador = auth()->user();
                $nombreAsignador = $asignador->name . ' ' . ($asignador->surname ?? '');
                
                // Obtener información completa de los nuevos usuarios
                $usuariosNuevos = \App\Models\User::whereIn('id', $nuevosUsuarios)->get();
                
                foreach ($usuariosNuevos as $usuario) {
                    try {
                        $nombreUsuario = $usuario->name . ' ' . ($usuario->surname ?? '');
                        
                        Mail::to($usuario->email)->send(
                            new TareaAsignadaMail(
                                $nombreUsuario,
                                $nombreAsignador,
                                $tarea,
                                $grupo,
                                $tarea->lista
                            )
                        );
                        
                        // 🆕 Crear notificación en el sistema
                        NotificationService::tareaAsignada(
                            $usuario->id,                    // userId - ID del usuario asignado
                            auth()->id(),                    // fromUserId - ID del usuario que asigna
                            $tarea->id,                      // tareaId - ID de la tarea
                            $grupo->id,                      // grupoId - ID del grupo
                            $tarea->name,                    // tareaNombre - Nombre de la tarea
                            $grupo->name,                    // grupoNombre - Nombre del grupo
                            $nombreAsignador                 // asignadorNombre - Nombre del asignador
                        );

                        Log::info('✅ Correo y notificación enviados a:', [
                            'email' => $usuario->email,
                            'nombre' => $nombreUsuario,
                            'tarea_id' => $tarea->id,
                            'grupo_id' => $grupo->id
                        ]);
                        
                        // Log::info('✅ Correo y notificación enviados a:', [
                        //     'email' => $usuario->email,
                        //     'nombre' => $nombreUsuario
                        // ]);
                        
                    } catch (\Exception $emailError) {
                        Log::error('❌ Error al enviar correo a ' . $usuario->email, [
                            'error' => $emailError->getMessage()
                        ]);
                        // Continuamos con los demás usuarios aunque falle uno
                    }
                }
            }
            
            // Recargar relaciones
            $tarea->load('assignedUsers');
            
            // Registrar en el timeline
            Timeline::create([
                'tarea_id' => $tarea->id,
                'user_id' => auth()->id(),
                'action' => 'assigned_members',
                'details' => [
                    'members' => $tarea->assignedUsers->map(function($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name . ' ' . ($user->surname ?? '')
                        ];
                    })->toArray()
                ]
            ]);
            
            Log::info('TareasController@assignMembers - Miembros asignados', [
                'tarea_id' => $tarea->id,
                'members_count' => $tarea->assignedUsers->count(),
                'nuevos_miembros' => $nuevosUsuarios->count(),
                'correos_enviados' => $nuevosUsuarios->count()
            ]);
            
            return response()->json([
                'message' => 200,
                'message_text' => 'Miembros asignados correctamente',
                'tarea' => $this->formatTarea($tarea),
                'members' => $tarea->assignedUsers->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'surname' => $user->surname,
                        'email' => $user->email,
                        'avatar' => $user->avatar ? $user->avatar : null,
                    ];
                }),
                'notificaciones_enviadas' => $nuevosUsuarios->count()
            ]);
            
        } catch (ModelNotFoundException $e) {
            Log::warning('TareasController@assignMembers - Tarea no encontrada', [
                'tarea_id' => $tareaId
            ]);

            return response()->json([
                'message' => 404,
                'message_text' => 'Tarea no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error('TareasController@assignMembers - Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 500,
                'message_text' => 'Error al asignar miembros'
            ], 500);
        }
    }

    /**
     * GET /api/tareas/{tareaId}/members
     */
    public function getMembers($tareaId)
    {
        try {
            Log::info('TareasController@getMembers - Iniciando', [
                'tarea_id' => $tareaId
            ]);

            $tarea = Tareas::with('assignedUsers')->findOrFail($tareaId);
            
            $members = $tarea->assignedUsers->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'email' => $user->email,
                    'avatar' => $user->avatar ? $user->avatar : null,
                ];
            });

            Log::info('TareasController@getMembers - Miembros obtenidos', [
                'tarea_id' => $tareaId,
                'members_count' => $members->count()
            ]);
            
            return response()->json([
                'message' => 200,
                'members' => $members
            ]);
            
        } catch (ModelNotFoundException $e) {
            Log::warning('TareasController@getMembers - Tarea no encontrada', [
                'tarea_id' => $tareaId
            ]);

            return response()->json([
                'message' => 404,
                'message_text' => 'Tarea no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error('TareasController@getMembers - Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 500,
                'message_text' => 'Error al obtener miembros'
            ], 500);
        }
    }


    /**
     * DELETE /api/tareas/{tareaId}/unassign-member/{userId}
     */
    public function unassignMember($tareaId, $userId)
    {
        try {
            Log::info('TareasController@unassignMember - Iniciando', [
                'tarea_id' => $tareaId,
                'user_id' => $userId
            ]);

            $tarea = Tareas::findOrFail($tareaId);
            
            // Validar permisos
            $grupo = $tarea->lista->grupo;
            if ($grupo->user_id !== auth()->id() && 
                !$grupo->sharedUsers->contains(auth()->id())) {
                return response()->json([
                    'message' => 403,
                    'message_text' => 'No tienes permiso para desasignar miembros'
                ], 403);
            }
            
            // Obtener datos del usuario antes de desasignar
            $user = \App\Models\User::find($userId);
            
            if (!$user) {
                return response()->json([
                    'message' => 404,
                    'message_text' => 'Usuario no encontrado'
                ], 404);
            }

            // Desasignar
            $tarea->assignedUsers()->detach($userId);
            
            // Registrar en el timeline
            Timeline::create([
                'tarea_id' => $tarea->id,
                'user_id' => auth()->id(),
                'action' => 'unassigned_member',
                'details' => [
                    'member' => [
                        'id' => $user->id,
                        'name' => $user->name . ' ' . ($user->surname ?? '')
                    ]
                ]
            ]);
            
            Log::info('TareasController@unassignMember - Miembro desasignado', [
                'tarea_id' => $tarea->id,
                'user_id' => $userId
            ]);
            
            return response()->json([
                'message' => 200,
                'message_text' => 'Miembro desasignado correctamente',
                'tarea' => $this->formatTarea($tarea)
            ]);
            
        } catch (ModelNotFoundException $e) {
            Log::warning('TareasController@unassignMember - Tarea no encontrada', [
                'tarea_id' => $tareaId
            ]);

            return response()->json([
                'message' => 404,
                'message_text' => 'Tarea no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error('TareasController@unassignMember - Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 500,
                'message_text' => 'Error al desasignar miembro'
            ], 500);
        }
    }

    private function formatTarea($tarea)
    {
        $tarea->load(['assignedUsers', 'lista.grupo', 'etiquetas', 'checklists']);
        
        return [
            'id' => $tarea->id,
            'name' => $tarea->name,
            'description' => $tarea->description,
            'status' => $tarea->status,
            'priority' => $tarea->priority,
            'start_date' => $tarea->start_date,
            'due_date' => $tarea->due_date,
            'assigned_members' => $tarea->assignedUsers->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'email' => $user->email,
                    'avatar' => $user->avatar ? $user->avatar : null,
                ];
            })
        ];
    }


    /**
     * 🆕 MÉTODO PRIVADO: Enviar notificaciones cuando una tarea se completa
     * 
     * Envía correos a:
     * 1. Creador de la tarea
     * 2. Todos los miembros asignados
     */
    private function enviarNotificacionesTareaCompletada($tarea)
    {
        try {
            // Cargar relaciones necesarias
            $tarea->load(['user', 'assignedUsers', 'lista.grupo']);

            $grupo = $tarea->lista->grupo;
            $lista = $tarea->lista;
            
            // Obtener información del usuario que completó la tarea
            $completador = auth()->user();
            $nombreCompletador = $completador->name . ' ' . ($completador->surname ?? '');

            // 📬 Colección para rastrear correos enviados (evitar duplicados)
            $correosEnviados = collect();

            // 1️⃣ Enviar correo al CREADOR de la tarea (si no es quien la completó)
            if ($tarea->user && $tarea->user->id !== $completador->id) {
                try {
                    $nombreCreador = $tarea->user->name . ' ' . ($tarea->user->surname ?? '');
                    
                    Mail::to($tarea->user->email)->send(
                        new \App\Mail\TareaCompletadaMail(
                            $nombreCreador,
                            $nombreCompletador,
                            $tarea,
                            $grupo,
                            $lista,
                            true // Es el creador
                        )
                    );
                    
                    // 🆕 Crear notificación en el sistema
                    NotificationService::tareaCompletada(
                        $tarea->user->id,
                        auth()->id(),
                        $tarea->id,
                        $grupo->id,
                        $tarea->name,
                        $grupo->name,
                        $nombreCompletador
                    );
                    
                    $correosEnviados->push($tarea->user->email);
                    
                    Log::info('✅ Correo y notificación enviados al creador:', [
                        'email' => $tarea->user->email,
                        'nombre' => $nombreCreador
                    ]);
                    
                } catch (\Exception $emailError) {
                    Log::error('❌ Error al enviar correo al creador:', [
                        'email' => $tarea->user->email,
                        'error' => $emailError->getMessage()
                    ]);
                }
            }

            // 2️⃣ Enviar correos a MIEMBROS ASIGNADOS (excepto quien completó y el creador)
            if ($tarea->assignedUsers && $tarea->assignedUsers->count() > 0) {
                foreach ($tarea->assignedUsers as $miembro) {
                    // Saltar si es quien completó la tarea
                    if ($miembro->id === $completador->id) {
                        continue;
                    }
                    
                    // Saltar si es el creador (ya se le envió)
                    if ($tarea->user && $miembro->id === $tarea->user->id) {
                        continue;
                    }
                    
                    // Evitar enviar correos duplicados
                    if ($correosEnviados->contains($miembro->email)) {
                        continue;
                    }
                    
                    try {
                        $nombreMiembro = $miembro->name . ' ' . ($miembro->surname ?? '');
                        
                        Mail::to($miembro->email)->send(
                            new \App\Mail\TareaCompletadaMail(
                                $nombreMiembro,
                                $nombreCompletador,
                                $tarea,
                                $grupo,
                                $lista,
                                false // No es el creador
                            )
                        );

                        // 🆕 Crear notificación en el sistema
                        NotificationService::tareaCompletada(
                            $miembro->id,
                            auth()->id(),
                            $tarea->id,
                            $grupo->id,
                            $tarea->name,
                            $grupo->name,
                            $nombreCompletador
                        );
                        
                        $correosEnviados->push($miembro->email);
                        
                        Log::info('✅ Correo enviado a miembro asignado:', [
                            'email' => $miembro->email,
                            'nombre' => $nombreMiembro
                        ]);
                        
                    } catch (\Exception $emailError) {
                        Log::error('❌ Error al enviar correo a miembro:', [
                            'email' => $miembro->email,
                            'error' => $emailError->getMessage()
                        ]);
                    }
                }
            }

            // 📊 Registrar en timeline
            Timeline::create([
                'tarea_id' => $tarea->id,
                'user_id' => auth()->id(),
                'action' => 'task_completed',
                'details' => [
                    'completed_by' => $nombreCompletador,
                    'notifications_sent' => $correosEnviados->count(),
                    'notified_emails' => $correosEnviados->toArray()
                ]
            ]);

            Log::info('📧 Notificaciones de tarea completada enviadas', [
                'tarea_id' => $tarea->id,
                'tarea_name' => $tarea->name,
                'total_notificaciones' => $correosEnviados->count(),
                'correos' => $correosEnviados->toArray()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error general al enviar notificaciones de tarea completada:', [
                'tarea_id' => $tarea->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

}