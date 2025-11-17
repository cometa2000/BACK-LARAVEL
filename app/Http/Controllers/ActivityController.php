<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\tasks\Tareas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    /**
     * Obtener todas las actividades del usuario autenticado
     * (actividades de tareas donde el usuario tiene acceso)
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $limit = $request->input('limit', 50);

            // Obtener los grupos donde el usuario tiene acceso (propios + compartidos)
            $ownedGrupoIds = $user->ownedGrupos()->pluck('grupos.id');
            $sharedGrupoIds = $user->sharedGrupos()->pluck('grupos.id');
            $grupoIds = $ownedGrupoIds->merge($sharedGrupoIds)->unique();

            // Obtener las tareas de esos grupos
            $tareaIds = Tareas::whereHas('lista', function($query) use ($grupoIds) {
                $query->whereIn('grupo_id', $grupoIds);
            })->pluck('id');

            // Obtener las actividades de esas tareas
            $activities = Activity::with(['user:id,name,surname,avatar', 'tarea:id,title'])
                ->whereIn('tarea_id', $tareaIds)
                ->recent($limit)
                ->get()
                ->map(function($activity) {
                    return [
                        'id' => $activity->id,
                        'user' => [
                            'id' => $activity->user->id,
                            'name' => $activity->user->name . ' ' . $activity->user->surname,
                            'avatar' => $activity->user->avatar,
                        ],
                        'tarea' => [
                            'id' => $activity->tarea->id,
                            'title' => $activity->tarea->title,
                        ],
                        'type' => $activity->type,
                        'description' => $activity->description,
                        'metadata' => $activity->metadata,
                        'icon' => $activity->icon,
                        'color' => $activity->color,
                        'created_at' => $activity->created_at->diffForHumans(),
                        'created_at_full' => $activity->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'activities' => $activities,
                'total' => $activities->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener actividades',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener actividades de una tarea específica
     */
    public function getByTarea($tareaId)
    {
        try {
            $user = Auth::user();
            
            // Verificar que el usuario tenga acceso a la tarea
            $tarea = Tareas::with('lista.grupo')->findOrFail($tareaId);
            $grupo = $tarea->lista->grupo;
            
            // Verificar acceso usando el método auxiliar
            if (!$user->hasAccessToGrupo($grupo->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a esta tarea'
                ], 403);
            }

            $activities = Activity::with(['user:id,name,surname,avatar'])
                ->byTarea($tareaId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($activity) {
                    return [
                        'id' => $activity->id,
                        'user' => [
                            'id' => $activity->user->id,
                            'name' => $activity->user->name . ' ' . $activity->user->surname,
                            'avatar' => $activity->user->avatar,
                        ],
                        'type' => $activity->type,
                        'description' => $activity->description,
                        'metadata' => $activity->metadata,
                        'icon' => $activity->icon,
                        'color' => $activity->color,
                        'created_at' => $activity->created_at->diffForHumans(),
                        'created_at_full' => $activity->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'activities' => $activities,
                'total' => $activities->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener actividades de la tarea',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva actividad (comentario)
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'tarea_id' => 'required|exists:tareas,id',
                'type' => 'required|string|in:comment,status_change,assignment,attachment,due_date,checklist,created,completed,deleted',
                'description' => 'required|string',
                'metadata' => 'nullable|array',
            ]);

            $user = Auth::user();
            
            // Verificar acceso a la tarea
            $tarea = Tareas::with('lista.grupo')->findOrFail($request->tarea_id);
            $grupo = $tarea->lista->grupo;
            
            // Usar el método auxiliar para verificar acceso
            if (!$user->hasAccessToGrupo($grupo->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a esta tarea'
                ], 403);
            }

            $activity = Activity::log(
                $user->id,
                $request->tarea_id,
                $request->type,
                $request->description,
                $request->metadata ?? []
            );

            $activity->load('user:id,name,surname,avatar');

            return response()->json([
                'success' => true,
                'message' => 'Actividad registrada exitosamente',
                'activity' => [
                    'id' => $activity->id,
                    'user' => [
                        'id' => $activity->user->id,
                        'name' => $activity->user->name . ' ' . $activity->user->surname,
                        'avatar' => $activity->user->avatar,
                    ],
                    'type' => $activity->type,
                    'description' => $activity->description,
                    'metadata' => $activity->metadata,
                    'icon' => $activity->icon,
                    'color' => $activity->color,
                    'created_at' => $activity->created_at->diffForHumans(),
                    'created_at_full' => $activity->created_at->format('Y-m-d H:i:s'),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar actividad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una actividad (solo comentarios y por el autor)
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $activity = Activity::findOrFail($id);

            // Solo el autor puede eliminar su actividad y solo si es un comentario
            if ($activity->user_id !== $user->id || $activity->type !== 'comment') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar esta actividad'
                ], 403);
            }

            $activity->delete();

            return response()->json([
                'success' => true,
                'message' => 'Actividad eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar actividad',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}