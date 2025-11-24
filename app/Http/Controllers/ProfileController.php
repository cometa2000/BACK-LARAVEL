<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\tasks\Tareas;
use App\Models\documents\Documentos;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ProfileController extends Controller
{
    /**
     * Obtener las tareas asignadas al usuario autenticado
     * ✅ VERSIÓN MÍNIMA: Sin adjuntos ni checklists para evitar errores
     */
    public function getUserTareas()
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 401,
                    'message_text' => 'Usuario no autenticado'
                ], 401);
            }

            \Log::info('📋 Obteniendo tareas para usuario:', ['user_id' => $user->id]);

            // ✅ VERSIÓN MÍNIMA: Solo relaciones básicas que funcionan
            $tareas = $user->assignedTareas()
                ->with([
                    'grupo:id,name',
                    'lista:id,name,grupo_id',
                    'etiquetas:id,name,color',
                    'assignedUsers:id,name,surname,avatar',
                    'actividades:id,tarea_id'
                ])
                ->orderBy('due_date', 'asc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($tarea) {
                    // Verificar vencimiento
                    $now = now();
                    $dueDate = $tarea->due_date ? \Carbon\Carbon::parse($tarea->due_date) : null;
                    $isOverdue = $dueDate ? $dueDate->isPast() && $tarea->status !== 'completada' : false;
                    $isDueSoon = $dueDate ? $dueDate->isToday() || ($dueDate->isFuture() && $dueDate->diffInDays($now) <= 3) : false;

                    // ✅ Conteos directos con DB para evitar cargar relaciones problemáticas
                    $adjuntosCount = DB::table('tarea_adjuntos')
                        ->where('tarea_id', $tarea->id)
                        ->whereNull('deleted_at')
                        ->count();

                    $checklistsCount = DB::table('checklists')
                        ->where('tarea_id', $tarea->id)
                        ->whereNull('deleted_at')
                        ->count();

                    return [
                        'id' => $tarea->id,
                        'name' => $tarea->name,
                        'description' => $tarea->description,
                        'status' => $tarea->status,
                        'priority' => $tarea->priority,
                        'due_date' => $tarea->due_date,
                        'is_overdue' => $isOverdue,
                        'is_due_soon' => $isDueSoon,
                        'grupo' => $tarea->grupo,
                        'lista' => $tarea->lista,
                        'etiquetas_count' => $tarea->etiquetas ? $tarea->etiquetas->count() : 0,
                        'adjuntos_count' => $adjuntosCount,
                        'actividades_count' => $tarea->actividades ? $tarea->actividades->count() : 0,
                        'checklist_items_total' => $checklistsCount,
                        'checklist_items_completed' => 0,
                        'checklist_progress' => 0,
                        'assigned_users' => $tarea->assignedUsers ? $tarea->assignedUsers->map(function($user) {
                            return [
                                'id' => $user->id,
                                'full_name' => $user->name . ' ' . $user->surname,
                                'avatar' => $user->avatar 
                                    ? env("APP_URL")."/storage/".$user->avatar 
                                    : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png',
                            ];
                        }) : [],
                        'created_at' => $tarea->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            \Log::info('✅ Tareas obtenidas:', ['count' => $tareas->count()]);

            return response()->json([
                'message' => 200,
                'tareas' => $tareas
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Error en getUserTareas: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'message' => 500,
                'message_text' => 'Error al obtener las tareas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener los documentos del usuario autenticado
     * ✅ CORREGIDO: Usar columnas REALES de la tabla documentos
     */
    public function getUserDocumentos(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 401,
                    'message_text' => 'Usuario no autenticado'
                ], 401);
            }

            $search = $request->get('search', '');

            \Log::info('📁 Obteniendo documentos para usuario:', [
                'user_id' => $user->id,
                'search' => $search
            ]);

            // ✅ Query optimizado
            $query = Documentos::where('user_id', $user->id)
                ->with('parent:id,name,type');

            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }

            $documentos = $query->orderBy('type', 'desc')
                ->orderBy('name', 'asc')
                ->get()
                ->map(function($doc) {
                    $data = [
                        'id' => $doc->id,
                        'name' => $doc->name,
                        'description' => $doc->description,
                        'type' => $doc->type,
                        'parent' => $doc->parent,
                        'file_path' => $doc->file_path,
                        'mime_type' => $doc->mime_type,
                        'created_at_human' => $doc->created_at->diffForHumans(),
                    ];

                    // ✅ Icono basado en el tipo de archivo
                    $data['icon'] = $this->getDocumentIcon($doc);

                    // ✅ URL del archivo
                    if ($doc->file_path) {
                        $data['file_url'] = env("APP_URL") . "/storage/" . $doc->file_path;
                    } else {
                        $data['file_url'] = null;
                    }

                    // ✅ Tamaño del archivo
                    if ($doc->size) {
                        $data['size_formatted'] = $doc->size;
                    } else {
                        $data['size_formatted'] = '-';
                    }

                    // Total de archivos para carpetas
                    if ($doc->type === 'folder') {
                        $data['total_files'] = Documentos::where('parent_id', $doc->id)->count();
                    } else {
                        $data['total_files'] = 0;
                    }

                    return $data;
                });

            // Separar carpetas y archivos
            $carpetas = $documentos->where('type', 'folder')->values();
            $archivos = $documentos->where('type', '!=', 'folder')->values();

            \Log::info('✅ Documentos obtenidos:', [
                'total' => $documentos->count(),
                'carpetas' => $carpetas->count(),
                'archivos' => $archivos->count()
            ]);

            return response()->json([
                'message' => 200,
                'documentos' => $documentos,
                'carpetas' => $carpetas,
                'archivos' => $archivos
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Error en getUserDocumentos: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'message' => 500,
                'message_text' => 'Error al obtener los documentos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas del usuario
     * ✅ OPTIMIZADO: Queries directas con DB para máxima velocidad
     */
    public function getUserStats()
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 401,
                    'message_text' => 'Usuario no autenticado'
                ], 401);
            }

            \Log::info('📊 Obteniendo estadísticas para usuario:', ['user_id' => $user->id]);

            // ✅ OPTIMIZACIÓN: Usar caché de 5 minutos para stats
            $cacheKey = "user_stats_{$user->id}";
            
            $stats = Cache::remember($cacheKey, 300, function() use ($user) {
                // ✅ Query optimizada con DB::raw para tareas
                $tareasStats = DB::table('tarea_user')
                    ->join('tareas', 'tarea_user.tarea_id', '=', 'tareas.id')
                    ->where('tarea_user.user_id', $user->id)
                    ->whereNull('tareas.deleted_at')
                    ->select([
                        DB::raw('COUNT(*) as total'),
                        DB::raw('SUM(CASE WHEN tareas.status = "pendiente" THEN 1 ELSE 0 END) as pendientes'),
                        DB::raw('SUM(CASE WHEN tareas.status = "en_progreso" THEN 1 ELSE 0 END) as en_progreso'),
                        DB::raw('SUM(CASE WHEN tareas.status = "completada" THEN 1 ELSE 0 END) as completadas')
                    ])
                    ->first();

                // ✅ Query optimizada con DB::raw para documentos
                $documentosStats = DB::table('documentos')
                    ->where('user_id', $user->id)
                    ->whereNull('deleted_at')
                    ->select([
                        DB::raw('COUNT(*) as total'),
                        DB::raw('SUM(CASE WHEN type = "folder" THEN 1 ELSE 0 END) as carpetas'),
                        DB::raw('SUM(CASE WHEN type = "file" THEN 1 ELSE 0 END) as archivos')
                    ])
                    ->first();

                // Calcular tasa de éxito
                $total = $tareasStats->total ?? 0;
                $completadas = $tareasStats->completadas ?? 0;
                $successRate = $total > 0 ? round(($completadas / $total) * 100, 1) : 0;

                return [
                    'tareas' => [
                        'total' => (int)($tareasStats->total ?? 0),
                        'pendientes' => (int)($tareasStats->pendientes ?? 0),
                        'en_progreso' => (int)($tareasStats->en_progreso ?? 0),
                        'completadas' => (int)($tareasStats->completadas ?? 0),
                    ],
                    'documentos' => [
                        'total' => (int)($documentosStats->total ?? 0),
                        'carpetas' => (int)($documentosStats->carpetas ?? 0),
                        'archivos' => (int)($documentosStats->archivos ?? 0),
                    ],
                    'success_rate' => $successRate
                ];
            });

            \Log::info('✅ Estadísticas obtenidas:', $stats);

            return response()->json([
                'message' => 200,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Error en getUserStats: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'message' => 500,
                'message_text' => 'Error al obtener las estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invalidar caché de stats cuando hay cambios
     */
    public function invalidateStatsCache($userId)
    {
        Cache::forget("user_stats_{$userId}");
    }

    /**
     * ✅ Obtener icono basado en tipo de archivo
     */
    private function getDocumentIcon($documento)
    {
        // Si es carpeta
        if ($documento->type === 'folder') {
            return './assets/media/svg/files/folder-document.svg';
        }

        // Si es archivo, basarse en mime_type
        if ($documento->mime_type) {
            $mime = $documento->mime_type;
            
            // PDFs
            if (str_contains($mime, 'pdf')) {
                return './assets/media/svg/files/pdf.svg';
            }
            
            // Word
            if (str_contains($mime, 'word') || str_contains($mime, 'document')) {
                return './assets/media/svg/files/doc.svg';
            }
            
            // Excel
            if (str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet')) {
                return './assets/media/svg/files/xml.svg';
            }
            
            // PowerPoint
            if (str_contains($mime, 'presentation') || str_contains($mime, 'powerpoint')) {
                return './assets/media/svg/files/pdf.svg';
            }
            
            // Imágenes
            if (str_contains($mime, 'image')) {
                return './assets/media/svg/files/upload.svg';
            }
            
            // Videos
            if (str_contains($mime, 'video')) {
                return './assets/media/svg/files/upload.svg';
            }
            
            // Texto
            if (str_contains($mime, 'text')) {
                return './assets/media/svg/files/doc.svg';
            }
        }

        // Por defecto
        return './assets/media/svg/files/blank.svg';
    }
}