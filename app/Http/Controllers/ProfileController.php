<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\tasks\Tareas;
use App\Models\documents\Documentos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Obtener las tareas asignadas al usuario autenticado
     * Incluye tanto tareas creadas como tareas asignadas
     */
    public function getUserTareas()
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            Log::info('ProfileController: Obteniendo tareas para usuario', ['user_id' => $user->id]);

            // Verificar que la relación assignedTareas existe
            if (!method_exists($user, 'assignedTareas')) {
                Log::error('ProfileController: Relación assignedTareas no existe en el modelo User');
                return response()->json([
                    'message' => 200,
                    'total' => 0,
                    'tareas' => [],
                    'info' => 'La relación assignedTareas no está configurada'
                ]);
            }

            // Obtener tareas asignadas al usuario - Carga progresiva de relaciones
            $tareasQuery = $user->assignedTareas()
                ->with([
                    'user:id,name,surname,avatar',
                    'grupo:id,name',
                    'lista:id,name',
                ])
                ->orderBy('due_date', 'asc')
                ->orderBy('created_at', 'desc');

            // Intentar cargar relaciones opcionales
            $availableRelations = [];
            
            // Verificar cada relación antes de cargarla
            if (method_exists(Tareas::class, 'etiquetas')) {
                $tareasQuery->with('etiquetas');
                $availableRelations[] = 'etiquetas';
            }
            
            if (method_exists(Tareas::class, 'checklists')) {
                $tareasQuery->with('checklists.items');
                $availableRelations[] = 'checklists';
            }
            
            if (method_exists(Tareas::class, 'adjuntos')) {
                $tareasQuery->with('adjuntos');
                $availableRelations[] = 'adjuntos';
            }
            
            if (method_exists(Tareas::class, 'assignedUsers')) {
                $tareasQuery->with('assignedUsers:id,name,surname,avatar');
                $availableRelations[] = 'assignedUsers';
            }
            
            if (method_exists(Tareas::class, 'actividades')) {
                $tareasQuery->with(['actividades' => function($query) {
                    $query->orderBy('created_at', 'desc')->limit(5);
                }]);
                $availableRelations[] = 'actividades';
            }

            $tareasAsignadas = $tareasQuery->get();
            
            Log::info('ProfileController: Tareas obtenidas', [
                'total' => $tareasAsignadas->count(),
                'relaciones_cargadas' => $availableRelations
            ]);

            // Transformar las tareas para el frontend
            $tareas = $tareasAsignadas->map(function($tarea) {
                // Calcular progreso de checklists (si existe la relación)
                $totalChecklistItems = 0;
                $completedChecklistItems = 0;
                $checklistProgress = 0;
                
                if (isset($tarea->checklists) && $tarea->checklists) {
                    $totalChecklistItems = $tarea->checklists->sum(function($checklist) {
                        return $checklist->items ? $checklist->items->count() : 0;
                    });
                    
                    $completedChecklistItems = $tarea->checklists->sum(function($checklist) {
                        return $checklist->items ? $checklist->items->where('completed', true)->count() : 0;
                    });
                    
                    $checklistProgress = $totalChecklistItems > 0 
                        ? round(($completedChecklistItems / $totalChecklistItems) * 100) 
                        : 0;
                }

                return [
                    'id' => $tarea->id,
                    'name' => $tarea->name,
                    'description' => $tarea->description,
                    'type_task' => $tarea->type_task ?? 'simple',
                    'priority' => $tarea->priority ?? 'medium',
                    'status' => $tarea->status ?? 'pendiente',
                    'start_date' => $tarea->start_date ? $tarea->start_date->format('Y-m-d') : null,
                    'due_date' => $tarea->due_date ? $tarea->due_date->format('Y-m-d') : null,
                    'is_overdue' => method_exists($tarea, 'isOverdue') ? $tarea->isOverdue() : false,
                    'is_due_soon' => method_exists($tarea, 'isDueSoon') ? $tarea->isDueSoon() : false,
                    'grupo' => $tarea->grupo ? [
                        'id' => $tarea->grupo->id,
                        'name' => $tarea->grupo->name,
                    ] : null,
                    'lista' => $tarea->lista ? [
                        'id' => $tarea->lista->id,
                        'name' => $tarea->lista->name,
                    ] : null,
                    'creator' => $tarea->user ? [
                        'id' => $tarea->user->id,
                        'name' => $tarea->user->name,
                        'surname' => $tarea->user->surname,
                        'full_name' => $tarea->user->name . ' ' . $tarea->user->surname,
                        'avatar' => $tarea->user->avatar ? env("APP_URL")."/storage/".$tarea->user->avatar : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png',
                    ] : null,
                    'assigned_users' => isset($tarea->assignedUsers) && $tarea->assignedUsers ? 
                        $tarea->assignedUsers->map(function($user) {
                            return [
                                'id' => $user->id,
                                'name' => $user->name,
                                'surname' => $user->surname,
                                'full_name' => $user->name . ' ' . $user->surname,
                                'avatar' => $user->avatar ? env("APP_URL")."/storage/".$user->avatar : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png',
                            ];
                        }) : [],
                    'etiquetas_count' => isset($tarea->etiquetas) ? $tarea->etiquetas->count() : 0,
                    'etiquetas' => isset($tarea->etiquetas) && $tarea->etiquetas ? 
                        $tarea->etiquetas->map(function($etiqueta) {
                            return [
                                'id' => $etiqueta->id,
                                'name' => $etiqueta->name,
                                'color' => $etiqueta->color ?? '#000000',
                            ];
                        }) : [],
                    'adjuntos_count' => isset($tarea->adjuntos) ? $tarea->adjuntos->count() : 0,
                    'checklist_progress' => $checklistProgress,
                    'checklist_items_total' => $totalChecklistItems,
                    'checklist_items_completed' => $completedChecklistItems,
                    'actividades_count' => isset($tarea->actividades) ? $tarea->actividades->count() : 0,
                    'recent_activities' => isset($tarea->actividades) && $tarea->actividades ? 
                        $tarea->actividades->take(3)->map(function($actividad) {
                            return [
                                'id' => $actividad->id,
                                'action' => $actividad->action ?? 'actividad',
                                'description' => $actividad->description ?? '',
                                'created_at' => $actividad->created_at->diffForHumans(),
                            ];
                        }) : [],
                    'created_at' => $tarea->created_at->format('Y-m-d H:i:s'),
                    'created_at_human' => $tarea->created_at->diffForHumans(),
                ];
            });

            return response()->json([
                'message' => 200,
                'total' => $tareas->count(),
                'tareas' => $tareas,
            ]);

        } catch (\Exception $e) {
            Log::error('ProfileController: Error al obtener tareas', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error al obtener las tareas',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Obtener los documentos del usuario autenticado
     * Solo documentos donde el user_id coincida con el usuario autenticado
     */
    public function getUserDocumentos(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            Log::info('ProfileController: Obteniendo documentos para usuario', ['user_id' => $user->id]);

            $search = $request->get('search', '');

            // Obtener todos los documentos del usuario (archivos y carpetas)
            $documentos = Documentos::where('user_id', $user->id)
                ->where('name', 'like', '%'.$search.'%')
                ->with([
                    'user:id,name,surname,avatar',
                    'sucursale:id,name',
                    'parent:id,name,type'
                ])
                ->orderBy('type', 'asc') // Carpetas primero
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info('ProfileController: Documentos obtenidos', ['total' => $documentos->count()]);

            // Separar carpetas y archivos
            $carpetas = $documentos->where('type', 'folder');
            $archivos = $documentos->where('type', 'file');

            // Transformar carpetas
            $carpetasTransformadas = $carpetas->map(function($carpeta) {
                $filesCount = $carpeta->children()->where('type', 'file')->count();
                
                return [
                    'id' => $carpeta->id,
                    'name' => $carpeta->name,
                    'type' => $carpeta->type,
                    'description' => $carpeta->description,
                    'parent_id' => $carpeta->parent_id,
                    'parent' => $carpeta->parent ? [
                        'id' => $carpeta->parent->id,
                        'name' => $carpeta->parent->name,
                    ] : null,
                    'files_count' => $filesCount,
                    'total_files' => method_exists($carpeta, 'countAllFiles') ? $carpeta->countAllFiles() : $filesCount,
                    'icon' => './assets/media/svg/files/folder-document.svg',
                    'sucursale' => $carpeta->sucursale ? [
                        'id' => $carpeta->sucursale->id,
                        'name' => $carpeta->sucursale->name,
                    ] : null,
                    'created_at' => $carpeta->created_at->format('Y-m-d H:i:s'),
                    'created_at_human' => $carpeta->created_at->diffForHumans(),
                ];
            });

            // Transformar archivos
            $archivosTransformados = $archivos->map(function($archivo) {
                // Determinar el icono según el tipo de archivo
                $icon = './assets/media/svg/files/blank.svg';
                
                if ($archivo->mime_type) {
                    if (str_contains($archivo->mime_type, 'pdf')) {
                        $icon = './assets/media/svg/files/pdf.svg';
                    } elseif (str_contains($archivo->mime_type, 'word') || str_contains($archivo->mime_type, 'document')) {
                        $icon = './assets/media/svg/files/doc.svg';
                    } elseif (str_contains($archivo->mime_type, 'excel') || str_contains($archivo->mime_type, 'spreadsheet')) {
                        $icon = './assets/media/svg/files/csv.svg';
                    } elseif (str_contains($archivo->mime_type, 'image')) {
                        $icon = './assets/media/svg/files/jpg.svg';
                    } elseif (str_contains($archivo->mime_type, 'zip') || str_contains($archivo->mime_type, 'compressed')) {
                        $icon = './assets/media/svg/files/zip.svg';
                    }
                }

                // Formatear el tamaño del archivo
                $sizeFormatted = $archivo->size ? $this->formatFileSize($archivo->size) : 'N/A';

                return [
                    'id' => $archivo->id,
                    'name' => $archivo->name,
                    'type' => $archivo->type,
                    'description' => $archivo->description,
                    'parent_id' => $archivo->parent_id,
                    'parent' => $archivo->parent ? [
                        'id' => $archivo->parent->id,
                        'name' => $archivo->parent->name,
                    ] : null,
                    'file_path' => $archivo->file_path,
                    'file_url' => $archivo->file_path ? env("APP_URL")."/storage/".$archivo->file_path : null,
                    'mime_type' => $archivo->mime_type,
                    'size' => $archivo->size,
                    'size_formatted' => $sizeFormatted,
                    'icon' => $icon,
                    'sucursale' => $archivo->sucursale ? [
                        'id' => $archivo->sucursale->id,
                        'name' => $archivo->sucursale->name,
                    ] : null,
                    'user' => [
                        'id' => $archivo->user->id,
                        'name' => $archivo->user->name,
                        'surname' => $archivo->user->surname ?? '',
                        'full_name' => $archivo->user->name . ' ' . ($archivo->user->surname ?? ''),
                    ],
                    'created_at' => $archivo->created_at->format('Y-m-d H:i:s'),
                    'created_at_human' => $archivo->created_at->diffForHumans(),
                ];
            });

            return response()->json([
                'message' => 200,
                'total' => $documentos->count(),
                'carpetas_count' => $carpetas->count(),
                'archivos_count' => $archivos->count(),
                'carpetas' => $carpetasTransformadas,
                'archivos' => $archivosTransformados,
                'documentos' => $carpetasTransformadas->concat($archivosTransformados)->values(),
            ]);

        } catch (\Exception $e) {
            Log::error('ProfileController: Error al obtener documentos', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'message' => 'Error al obtener los documentos',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Formatear el tamaño del archivo en KB, MB, GB
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Obtener estadísticas del perfil del usuario
     */
    public function getUserStats()
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            Log::info('ProfileController: Obteniendo estadísticas para usuario', ['user_id' => $user->id]);

            // Verificar que la relación existe
            if (!method_exists($user, 'assignedTareas')) {
                return response()->json([
                    'message' => 200,
                    'stats' => [
                        'tareas' => [
                            'total' => 0,
                            'pendientes' => 0,
                            'en_progreso' => 0,
                            'completadas' => 0,
                        ],
                        'documentos' => [
                            'total' => 0,
                            'carpetas' => 0,
                            'archivos' => 0,
                        ],
                        'success_rate' => 0,
                    ],
                ]);
            }

            // Contar tareas por estado
            $totalTareas = $user->assignedTareas()->count();
            $tareasPendientes = $user->assignedTareas()->where('status', 'pendiente')->count();
            $tareasEnProgreso = $user->assignedTareas()->where('status', 'en_progreso')->count();
            $tareasCompletadas = $user->assignedTareas()->where('status', 'completada')->count();
            
            // Contar documentos
            $totalDocumentos = Documentos::where('user_id', $user->id)->count();
            $totalCarpetas = Documentos::where('user_id', $user->id)->where('type', 'folder')->count();
            $totalArchivos = Documentos::where('user_id', $user->id)->where('type', 'file')->count();

            // Calcular tasa de éxito
            $successRate = $totalTareas > 0 ? round(($tareasCompletadas / $totalTareas) * 100) : 0;

            Log::info('ProfileController: Estadísticas calculadas', [
                'total_tareas' => $totalTareas,
                'total_documentos' => $totalDocumentos
            ]);

            return response()->json([
                'message' => 200,
                'stats' => [
                    'tareas' => [
                        'total' => $totalTareas,
                        'pendientes' => $tareasPendientes,
                        'en_progreso' => $tareasEnProgreso,
                        'completadas' => $tareasCompletadas,
                    ],
                    'documentos' => [
                        'total' => $totalDocumentos,
                        'carpetas' => $totalCarpetas,
                        'archivos' => $totalArchivos,
                    ],
                    'success_rate' => $successRate,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('ProfileController: Error al obtener estadísticas', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'message' => 'Error al obtener las estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}