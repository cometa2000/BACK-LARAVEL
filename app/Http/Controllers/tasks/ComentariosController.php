<?php

namespace App\Http\Controllers\tasks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\tasks\Comentario;
use App\Models\tasks\Actividad;
use Illuminate\Support\Facades\Log;  // ✅ Agregar esta línea

class ComentariosController extends Controller
{
    /**
     * Obtener comentarios y actividades de una tarea
     */
    public function index($tareaId)
    {
        Log::info("📥 Solicitando timeline para tarea ID: {$tareaId}");
        
        $comentarios = Comentario::with('user')
            ->where('tarea_id', $tareaId)
            ->get()
            ->map(function($comentario) {
                return [
                    'id' => $comentario->id,
                    'content' => $comentario->content,
                    'user' => [
                        'id' => $comentario->user->id,
                        'name' => $comentario->user->name . ' ' . ($comentario->user->surname ?? ''),
                        'avatar' => $comentario->user->avatar 
                            ? $comentario->user->avatar 
                            : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'
                    ],
                    'created_at' => $comentario->created_at->format('d M Y, H:i'),
                    'updated_at' => $comentario->updated_at->format('d M Y, H:i'),
                    'is_edited' => $comentario->created_at != $comentario->updated_at,
                    'type' => 'comentario',
                    // ✅ Agregar timestamp para ordenamiento
                    'timestamp' => $comentario->created_at->timestamp
                ];
            });

        $actividades = Actividad::with('user')
            ->where('tarea_id', $tareaId)
            ->get()
            ->map(function($actividad) {
                return [
                    'id' => $actividad->id,
                    'type' => 'actividad',
                    'action_type' => $actividad->type,
                    'description' => $actividad->description,
                    'changes' => $actividad->changes,
                    'user' => [
                        'id' => $actividad->user->id,
                        'name' => $actividad->user->name . ' ' . ($actividad->user->surname ?? ''),
                        'avatar' => $actividad->user->avatar 
                            ? $actividad->user->avatar 
                            : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'
                    ],
                    'created_at' => $actividad->created_at->format('d M Y, H:i'),
                    // ✅ Agregar timestamp para ordenamiento
                    'timestamp' => $actividad->created_at->timestamp
                ];
            });

        // ✅ Combinar y ordenar por timestamp
        $timeline = $comentarios->concat($actividades)
            ->sortByDesc('timestamp')
            ->values()
            ->map(function($item) {
                // Eliminar timestamp antes de enviarlo al frontend
                unset($item['timestamp']);
                return $item;
            });

        Log::info("✅ Timeline generado: {$timeline->count()} items", [
            'comentarios' => $comentarios->count(),
            'actividades' => $actividades->count()
        ]);

        return response()->json([
            'message' => 200,
            'timeline' => $timeline
        ]);
    }

    /**
     * Crear un nuevo comentario
     */
    public function store(Request $request, $tareaId)
    {
        $request->validate([
            'content' => 'required|string|min:1'
        ]);

        Log::info("💬 Creando comentario para tarea ID: {$tareaId}");

        $comentario = Comentario::create([
            'content' => $request->content,
            'tarea_id' => $tareaId,
            'user_id' => auth()->id()
        ]);

        // Registrar actividad
        $actividad = Actividad::create([
            'type' => 'comment_added',
            'description' => 'añadió un comentario',
            'tarea_id' => $tareaId,
            'user_id' => auth()->id()
        ]);

        $comentario->load('user');
        $actividad->load('user');

        Log::info("✅ Comentario creado con ID: {$comentario->id}");

        // ✅ Devolver AMBOS: comentario Y actividad
        return response()->json([
            'message' => 200,
            'comentario' => [
                'id' => $comentario->id,
                'content' => $comentario->content,
                'user' => [
                    'id' => $comentario->user->id,
                    'name' => $comentario->user->name . ' ' . ($comentario->user->surname ?? ''),
                    'avatar' => $comentario->user->avatar 
                        ? $comentario->user->avatar 
                        : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'
                ],
                'created_at' => $comentario->created_at->format('d M Y, H:i'),
                'updated_at' => $comentario->updated_at->format('d M Y, H:i'),
                'is_edited' => false,
                'type' => 'comentario'
            ],
            // ✅ AGREGAR LA ACTIVIDAD
            'actividad' => [
                'id' => $actividad->id,
                'type' => 'actividad',
                'action_type' => $actividad->type,
                'description' => $actividad->description,
                'changes' => $actividad->changes,
                'user' => [
                    'id' => $actividad->user->id,
                    'name' => $actividad->user->name . ' ' . ($actividad->user->surname ?? ''),
                    'avatar' => $actividad->user->avatar 
                        ? $actividad->user->avatar 
                        : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'
                ],
                'created_at' => $actividad->created_at->format('d M Y, H:i'),
            ]
        ]);
    }

    /**
     * Actualizar un comentario
     */
    public function update(Request $request, $tareaId, $comentarioId)
    {
        $request->validate([
            'content' => 'required|string|min:1'
        ]);

        Log::info("✏️ Actualizando comentario ID: {$comentarioId}");

        $comentario = Comentario::where('tarea_id', $tareaId)
            ->where('id', $comentarioId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $comentario->update([
            'content' => $request->content
        ]);

        $comentario->load('user');

        Log::info("✅ Comentario actualizado");

        return response()->json([
            'message' => 200,
            'comentario' => [
                'id' => $comentario->id,
                'content' => $comentario->content,
                'user' => [
                    'id' => $comentario->user->id,
                    'name' => $comentario->user->name . ' ' . ($comentario->user->surname ?? ''),
                    'avatar' => $comentario->user->avatar 
                        ? $comentario->user->avatar 
                        : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'
                ],
                'created_at' => $comentario->created_at->format('d M Y, H:i'),
                'updated_at' => $comentario->updated_at->format('d M Y, H:i'),
                'is_edited' => true,
                'type' => 'comentario'
            ]
        ]);
    }

    /**
     * Eliminar un comentario
     */
    public function destroy($tareaId, $comentarioId)
    {
        Log::info("🗑️ Eliminando comentario ID: {$comentarioId}");

        $comentario = Comentario::where('tarea_id', $tareaId)
            ->where('id', $comentarioId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $comentario->delete();

        Log::info("✅ Comentario eliminado");

        return response()->json([
            'message' => 200
        ]);
    }
}