<?php

namespace App\Http\Controllers\tasks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\tasks\Tareas;
use App\Models\tasks\Actividad;
use Illuminate\Support\Facades\Log;
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
                'grupo'
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

            // Buscar tarea con todas sus relaciones
            $tarea = Tareas::with([
                'etiquetas',
                'checklists.items',
                'comentarios.user',
                'actividades.user',
                'user',
                'lista',
                'grupo'
            ])->findOrFail($id);

            Log::info('TareasController@show - Tarea encontrada', [
                'tarea_id' => $tarea->id,
                'tarea_name' => $tarea->name
            ]);

            // ✅ Cargar adjuntos de forma separada y segura
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
                'checklists' => $tarea->checklists,
                'comentarios' => $tarea->comentarios,
                'user' => $tarea->user,
                'lista' => $tarea->lista,
                'grupo' => $tarea->grupo,
                
                // ✅ ADJUNTOS procesados
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

            return response()->json([
                'message' => 200,
                'tarea' => $tareaData
            ]);

        } catch (ModelNotFoundException $e) {
            Log::warning('TareasController@show - Tarea no encontrada', [
                'tarea_id' => $id
            ]);

            return response()->json([
                'message' => 404,
                'error' => 'Tarea no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error('TareasController@show - Error', [
                'tarea_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 500,
                'error' => 'Error al cargar la tarea',
                'details' => config('app.debug') ? $e->getMessage() : null
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
                'grupo_id' => 'nullable|exists:grupos,id',
                'lista_id' => 'nullable|exists:listas,id',
            ]);

            $tarea = Tareas::create($request->all());

            // Registrar actividad
            Actividad::create([
                'type' => 'created',
                'description' => 'creó la tarea',
                'tarea_id' => $tarea->id,
                'user_id' => auth()->id(),
            ]);

            Log::info('TareasController@store - Tarea creada', [
                'tarea_id' => $tarea->id
            ]);

            return response()->json([
                'message' => 201,
                'tarea' => $tarea
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('TareasController@store - Validación fallida', [
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
                'error' => 'Error al crear la tarea'
            ], 500);
        }
    }

    /**
     * MÉTODO UPDATE
     * Actualizar una tarea existente
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
                'start_date' => 'nullable|date',
                'due_date' => 'nullable|date|after_or_equal:start_date',
                'priority' => 'sometimes|in:low,medium,high',
                'status' => 'sometimes|in:pendiente,en_progreso,completada',
            ]);

            // Guardar valores antiguos para el registro de actividad
            $oldData = [
                'start_date' => $tarea->start_date,
                'due_date' => $tarea->due_date,
                'status' => $tarea->status,
                'description' => $tarea->description,
            ];

            // Actualizar la tarea
            $tarea->update($request->all());

            // Registrar actividades según los cambios
            if ($request->has('start_date') && $oldData['start_date'] != $request->start_date) {
                Actividad::create([
                    'type' => 'date_changed',
                    'description' => 'actualizó la fecha de inicio',
                    'tarea_id' => $tarea->id,
                    'user_id' => auth()->id(),
                    'changes' => json_encode([
                        'old_date' => $oldData['start_date'],
                        'new_date' => $request->start_date
                    ])
                ]);
            }

            if ($request->has('due_date') && $oldData['due_date'] != $request->due_date) {
                Actividad::create([
                    'type' => 'date_changed',
                    'description' => 'actualizó la fecha de vencimiento',
                    'tarea_id' => $tarea->id,
                    'user_id' => auth()->id(),
                    'changes' => json_encode([
                        'old_date' => $oldData['due_date'],
                        'new_date' => $request->due_date
                    ])
                ]);
            }

            if ($request->has('status') && $oldData['status'] != $request->status) {
                Actividad::create([
                    'type' => 'status_changed',
                    'description' => 'cambió el estado',
                    'tarea_id' => $tarea->id,
                    'user_id' => auth()->id(),
                    'changes' => json_encode([
                        'old_status' => $oldData['status'],
                        'new_status' => $request->status
                    ])
                ]);
            }

            if ($request->has('description') && $oldData['description'] != $request->description) {
                Actividad::create([
                    'type' => 'description_changed',
                    'description' => 'actualizó la descripción',
                    'tarea_id' => $tarea->id,
                    'user_id' => auth()->id(),
                ]);
            }

            // Recargar con relaciones
            $tarea->load(['etiquetas', 'checklists.items', 'user', 'lista', 'grupo']);

            Log::info('TareasController@update - Tarea actualizada', [
                'tarea_id' => $tarea->id
            ]);

            return response()->json([
                'message' => 200,
                'tarea' => $tarea
            ]);

        } catch (ModelNotFoundException $e) {
            Log::warning('TareasController@update - Tarea no encontrada', [
                'tarea_id' => $id
            ]);

            return response()->json([
                'message' => 404,
                'error' => 'Tarea no encontrada'
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('TareasController@update - Validación fallida', [
                'tarea_id' => $id,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'message' => 422,
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('TareasController@update - Error', [
                'tarea_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 500,
                'error' => 'Error al actualizar la tarea'
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
}