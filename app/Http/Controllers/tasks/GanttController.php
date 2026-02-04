<?php

namespace App\Http\Controllers\tasks;

use App\Http\Controllers\Controller;
use App\Models\tasks\Tareas;
use App\Models\tasks\Grupos;
use App\Models\tasks\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class GanttController extends Controller
{
    /**
     * 📊 Obtener datos para el diagrama de Gantt
     * 
     * Parámetros de filtrado:
     * - start_date: Fecha inicial del rango
     * - end_date: Fecha final del rango
     * - filter_type: 'all' | 'workspace' | 'grupo' | 'shared'
     * - filter_id: ID del workspace o grupo (cuando aplique)
     */
    public function getGanttData(Request $request)
    {
        try {
            $userId = Auth::id();
            
            // 📅 Validar y obtener rango de fechas
            $startDate = $request->input('start_date') 
                ? Carbon::parse($request->input('start_date')) 
                : Carbon::now()->startOfMonth();
            
            $endDate = $request->input('end_date') 
                ? Carbon::parse($request->input('end_date')) 
                : Carbon::now()->endOfMonth();

            // 🔍 Tipo de filtro
            $filterType = $request->input('filter_type', 'all'); // all, workspace, grupo, shared
            $filterId = $request->input('filter_id'); // ID del workspace o grupo

            // 📋 Query base de tareas
            $query = Tareas::with([
                'user:id,name,surname,avatar',
                'grupo:id,name,workspace_id,user_id',
                'grupo.workspace:id,name',
                'lista:id,name',
                'assignedUsers:id,name,surname,avatar'
            ])
            ->where(function($q) use ($startDate, $endDate) {
                // Tareas que tengan fechas dentro del rango O que crucen el rango
                $q->where(function($subQ) use ($startDate, $endDate) {
                    $subQ->whereBetween('start_date', [$startDate, $endDate])
                         ->orWhereBetween('due_date', [$startDate, $endDate])
                         ->orWhere(function($dateQ) use ($startDate, $endDate) {
                             $dateQ->where('start_date', '<=', $startDate)
                                   ->where('due_date', '>=', $endDate);
                         });
                });
            })
            ->whereNotNull('start_date')
            ->whereNotNull('due_date');

            // 🎯 Aplicar filtros según el tipo
            switch ($filterType) {
                case 'workspace':
                    // Tareas de un workspace específico
                    if ($filterId) {
                        $query->whereHas('grupo', function($q) use ($filterId, $userId) {
                            $q->where('workspace_id', $filterId)
                              ->where(function($accessQ) use ($userId) {
                                  $accessQ->where('user_id', $userId)
                                          ->orWhereHas('sharedUsers', function($sharedQ) use ($userId) {
                                              $sharedQ->where('users.id', $userId);
                                          });
                              });
                        });
                    }
                    break;

                case 'grupo':
                    // Tareas de un grupo específico
                    if ($filterId) {
                        $query->where('grupo_id', $filterId)
                              ->whereHas('grupo', function($q) use ($userId) {
                                  $q->where(function($accessQ) use ($userId) {
                                      $accessQ->where('user_id', $userId)
                                              ->orWhereHas('sharedUsers', function($sharedQ) use ($userId) {
                                                  $sharedQ->where('users.id', $userId);
                                              });
                                  });
                              });
                    }
                    break;

                case 'shared':
                    // Solo tareas compartidas (grupos donde NO es propietario)
                    $query->whereHas('grupo', function($q) use ($userId) {
                        $q->where('user_id', '!=', $userId)
                          ->whereHas('sharedUsers', function($sharedQ) use ($userId) {
                              $sharedQ->where('users.id', $userId);
                          });
                    });
                    break;

                case 'all':
                default:
                    // Todas las tareas accesibles por el usuario
                    $query->where(function($q) use ($userId) {
                        // Tareas propias
                        $q->whereHas('grupo', function($grupoQ) use ($userId) {
                            $grupoQ->where('user_id', $userId);
                        })
                        // O tareas de grupos compartidos
                        ->orWhereHas('grupo.sharedUsers', function($sharedQ) use ($userId) {
                            $sharedQ->where('users.id', $userId);
                        })
                        // O tareas asignadas directamente
                        ->orWhereHas('assignedUsers', function($assignedQ) use ($userId) {
                            $assignedQ->where('users.id', $userId);
                        });
                    });
                    break;
            }

            // 📊 Obtener tareas y formatear para Gantt
            $tareas = $query->orderBy('start_date', 'asc')->get();

            // 🎨 Formatear datos para el gráfico
            $ganttData = $tareas->map(function($tarea) use ($userId) {
                return [
                    'id' => $tarea->id,
                    'name' => $tarea->name,
                    'start_date' => $tarea->start_date->format('Y-m-d'),
                    'due_date' => $tarea->due_date->format('Y-m-d'),
                    'duration_days' => $tarea->start_date->diffInDays($tarea->due_date) + 1,
                    'status' => $tarea->status,
                    'priority' => $tarea->priority,
                    'progress' => $this->calculateProgress($tarea),
                    
                    // Información del grupo/workspace
                    'grupo' => [
                        'id' => $tarea->grupo->id ?? null,
                        'name' => $tarea->grupo->name ?? 'Sin grupo',
                        'workspace_id' => $tarea->grupo->workspace_id ?? null,
                        'workspace_name' => $tarea->grupo->workspace->name ?? 'Sin workspace',
                    ],
                    
                    // Información de lista
                    'lista' => [
                        'id' => $tarea->lista->id ?? null,
                        'name' => $tarea->lista->name ?? 'Sin lista',
                    ],
                    
                    // Propietario y asignados
                    'owner' => [
                        'id' => $tarea->user->id ?? null,
                        'name' => $tarea->user ? ($tarea->user->name . ' ' . $tarea->user->surname) : 'Desconocido',
                        'avatar' => $tarea->user->avatar ?? null,
                    ],
                    
                    'assigned_users' => $tarea->assignedUsers->map(function($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name . ' ' . $user->surname,
                            'avatar' => $user->avatar,
                        ];
                    }),
                    
                    // Metadatos
                    'is_owner' => $tarea->user_id == $userId,
                    'is_assigned' => $tarea->assignedUsers->contains('id', $userId),
                    'is_overdue' => $tarea->due_date->isPast() && $tarea->status !== 'completada',
                    
                    // Color según estado/prioridad
                    'color' => $this->getTaskColor($tarea),
                ];
            });

            // 📈 Estadísticas adicionales
            $stats = [
                'total_tasks' => $ganttData->count(),
                'completed' => $ganttData->where('status', 'completada')->count(),
                'in_progress' => $ganttData->where('status', 'en_progreso')->count(),
                'pending' => $ganttData->where('status', 'pendiente')->count(),
                'overdue' => $ganttData->where('is_overdue', true)->count(),
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
            ];

            return response()->json([
                'message' => 200,
                'gantt_data' => $ganttData->values(),
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 500,
                'error' => 'Error al obtener datos del Gantt',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 📋 Obtener opciones de filtrado (workspaces y grupos disponibles)
     */
    public function getFilterOptions()
    {
        try {
            $userId = Auth::id();

            // 🏢 Workspaces del usuario
            $workspaces = Workspace::where('user_id', $userId)
                ->withCount('grupos')
                ->orderBy('name')
                ->get(['id', 'name', 'color']);

            // 📁 Grupos accesibles (propios + compartidos)
            $grupos = Grupos::where(function($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhereHas('sharedUsers', function($q) use ($userId) {
                          $q->where('users.id', $userId);
                      });
            })
            ->with('workspace:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'workspace_id', 'user_id'])
            ->map(function($grupo) use ($userId) {
                return [
                    'id' => $grupo->id,
                    'name' => $grupo->name,
                    'workspace_id' => $grupo->workspace_id,
                    'workspace_name' => $grupo->workspace->name ?? 'Sin workspace',
                    'is_owner' => $grupo->user_id == $userId,
                ];
            });

            return response()->json([
                'message' => 200,
                'workspaces' => $workspaces,
                'grupos' => $grupos,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 500,
                'error' => 'Error al obtener opciones de filtrado',
            ], 500);
        }
    }

    /**
     * 🎨 Determinar color de la tarea según estado y prioridad
     */
    private function getTaskColor($tarea)
    {
        // Si está completada, verde
        if ($tarea->status === 'completada') {
            return '#10b981'; // green-500
        }

        // Si está vencida, rojo
        if ($tarea->due_date->isPast() && $tarea->status !== 'completada') {
            return '#ef4444'; // red-500
        }

        // Por prioridad
        switch ($tarea->priority) {
            case 'high':
                return '#f59e0b'; // amber-500
            case 'medium':
                return '#3b82f6'; // blue-500
            case 'low':
                return '#6b7280'; // gray-500
            default:
                return '#6366f1'; // indigo-500
        }
    }

    /**
     * 📊 Calcular progreso de la tarea
     */
    private function calculateProgress($tarea)
    {
        if ($tarea->status === 'completada') {
            return 100;
        }

        if ($tarea->status === 'en_progreso') {
            // Si tiene checklists, calcular basado en eso
            $totalChecklistProgress = $tarea->getTotalChecklistProgress();
            if ($totalChecklistProgress > 0) {
                return $totalChecklistProgress;
            }
            return 50; // Por defecto si está en progreso
        }

        return 0; // Pendiente
    }
}