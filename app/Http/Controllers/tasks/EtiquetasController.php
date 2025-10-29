<?php

namespace App\Http\Controllers\tasks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\tasks\Etiqueta;
use App\Models\tasks\Actividad;
use Illuminate\Support\Facades\Log;

class EtiquetasController extends Controller
{
    /**
     * Obtener todas las etiquetas de una tarea
     */
    public function index($tareaId)
    {
        Log::info("📥 Obteniendo etiquetas de la tarea ID: {$tareaId}");
        
        $etiquetas = Etiqueta::where('tarea_id', $tareaId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'message' => 200,
            'etiquetas' => $etiquetas
        ]);
    }

    /**
     * Crear una nueva etiqueta
     */
    public function store(Request $request, $tareaId)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'required|string|max:20'
        ]);

        Log::info("🏷️ Creando etiqueta para tarea ID: {$tareaId}");

        $etiqueta = Etiqueta::create([
            'name' => $request->name,
            'color' => $request->color,
            'tarea_id' => $tareaId
        ]);

        // Registrar actividad
        Actividad::create([
            'type' => 'label_added',
            'description' => 'añadió la etiqueta "' . $etiqueta->name . '"',
            'tarea_id' => $tareaId,
            'user_id' => auth()->id(),
            'changes' => json_encode([
                'label_name' => $etiqueta->name,
                'label_color' => $etiqueta->color
            ])
        ]);

        Log::info("✅ Etiqueta creada con ID: {$etiqueta->id}");

        return response()->json([
            'message' => 200,
            'etiqueta' => $etiqueta
        ], 201);
    }

    /**
     * Actualizar una etiqueta
     */
    public function update(Request $request, $tareaId, $etiquetaId)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'required|string|max:20'
        ]);

        Log::info("✏️ Actualizando etiqueta ID: {$etiquetaId}");

        $etiqueta = Etiqueta::where('tarea_id', $tareaId)
            ->where('id', $etiquetaId)
            ->firstOrFail();

        $oldName = $etiqueta->name;

        $etiqueta->update([
            'name' => $request->name,
            'color' => $request->color
        ]);

        // Registrar actividad
        Actividad::create([
            'type' => 'label_updated',
            'description' => 'actualizó la etiqueta de "' . $oldName . '" a "' . $etiqueta->name . '"',
            'tarea_id' => $tareaId,
            'user_id' => auth()->id(),
            'changes' => json_encode([
                'old_name' => $oldName,
                'new_name' => $etiqueta->name,
                'color' => $etiqueta->color
            ])
        ]);

        Log::info("✅ Etiqueta actualizada");

        return response()->json([
            'message' => 200,
            'etiqueta' => $etiqueta
        ]);
    }

    /**
     * Eliminar una etiqueta
     */
    public function destroy($tareaId, $etiquetaId)
    {
        Log::info("🗑️ Eliminando etiqueta ID: {$etiquetaId}");

        $etiqueta = Etiqueta::where('tarea_id', $tareaId)
            ->where('id', $etiquetaId)
            ->firstOrFail();

        $labelName = $etiqueta->name;

        $etiqueta->delete();

        // Registrar actividad
        Actividad::create([
            'type' => 'label_deleted',
            'description' => 'eliminó la etiqueta "' . $labelName . '"',
            'tarea_id' => $tareaId,
            'user_id' => auth()->id()
        ]);

        Log::info("✅ Etiqueta eliminada");

        return response()->json([
            'message' => 200,
            'message_text' => 'Etiqueta eliminada exitosamente'
        ]);
    }
}