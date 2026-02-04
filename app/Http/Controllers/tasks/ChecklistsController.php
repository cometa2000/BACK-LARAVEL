<?php

namespace App\Http\Controllers\tasks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\tasks\Checklist;
use App\Models\tasks\ChecklistItem;
use App\Models\tasks\Actividad;
use Illuminate\Support\Facades\Log;

class ChecklistsController extends Controller
{
    /**
     * Obtener todos los checklists de una tarea
     */
    public function index($tareaId)
    {
        Log::info("📥 Obteniendo checklists de la tarea ID: {$tareaId}");
        
        $checklists = Checklist::with('items')
            ->where('tarea_id', $tareaId)
            ->orderBy('orden', 'asc')
            ->get()
            ->map(function($checklist) {
                return [
                    'id' => $checklist->id,
                    'name' => $checklist->name,
                    'orden' => $checklist->orden,
                    'progress' => $checklist->progress,
                    'items' => $checklist->items
                ];
            });

        return response()->json([
            'message' => 200,
            'checklists' => $checklists
        ]);
    }

    /**
     * Crear un nuevo checklist
     */
    public function store(Request $request, $tareaId)
    {
        $request->validate([
            'name' => 'required|string|max:100'
        ]);

        Log::info("✅ Creando checklist para tarea ID: {$tareaId}");

        // Obtener el siguiente orden
        $maxOrden = Checklist::where('tarea_id', $tareaId)->max('orden') ?? 0;

        $checklist = Checklist::create([
            'name' => $request->name,
            'tarea_id' => $tareaId,
            'orden' => $maxOrden + 1
        ]);

        // Registrar actividad
        Actividad::create([
            'type' => 'checklist_added',
            'description' => 'añadió el checklist "' . $checklist->name . '"',
            'tarea_id' => $tareaId,
            'user_id' => auth()->id()
        ]);

        Log::info("✅ Checklist creado con ID: {$checklist->id}");

        return response()->json([
            'message' => 200,
            'checklist' => [
                'id' => $checklist->id,
                'name' => $checklist->name,
                'orden' => $checklist->orden,
                'progress' => 0,
                'items' => []
            ]
        ], 201);
    }

    /**
     * Actualizar un checklist
     */
    public function update(Request $request, $tareaId, $checklistId)
    {
        $request->validate([
            'name' => 'required|string|max:100'
        ]);

        Log::info("✏️ Actualizando checklist ID: {$checklistId}");

        $checklist = Checklist::where('tarea_id', $tareaId)
            ->where('id', $checklistId)
            ->firstOrFail();

        $oldName = $checklist->name;

        $checklist->update([
            'name' => $request->name
        ]);

        // Registrar actividad
        Actividad::create([
            'type' => 'checklist_updated',
            'description' => 'renombró el checklist de "' . $oldName . '" a "' . $checklist->name . '"',
            'tarea_id' => $tareaId,
            'user_id' => auth()->id()
        ]);

        Log::info("✅ Checklist actualizado");

        return response()->json([
            'message' => 200,
            'checklist' => $checklist
        ]);
    }

    /**
     * Eliminar un checklist
     */
    public function destroy($tareaId, $checklistId)
    {
        Log::info("🗑️ Eliminando checklist ID: {$checklistId}");

        $checklist = Checklist::where('tarea_id', $tareaId)
            ->where('id', $checklistId)
            ->firstOrFail();

        $checklistName = $checklist->name;

        $checklist->delete();

        // Registrar actividad
        Actividad::create([
            'type' => 'checklist_deleted',
            'description' => 'eliminó el checklist "' . $checklistName . '"',
            'tarea_id' => $tareaId,
            'user_id' => auth()->id()
        ]);

        Log::info("✅ Checklist eliminado");

        return response()->json([
            'message' => 200,
            'message_text' => 'Checklist eliminado exitosamente'
        ]);
    }

    /**
     * Agregar un item al checklist
     */
    public function addItem(Request $request, $tareaId, $checklistId)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        Log::info("➕ Añadiendo item al checklist ID: {$checklistId}");

        $checklist = Checklist::where('tarea_id', $tareaId)
            ->where('id', $checklistId)
            ->firstOrFail();

        // Obtener el siguiente orden
        $maxOrden = ChecklistItem::where('checklist_id', $checklistId)->max('orden') ?? 0;

        $item = ChecklistItem::create([
            'name' => $request->name,
            'checklist_id' => $checklistId,
            'orden' => $maxOrden + 1
        ]);

        // Registrar actividad
        Actividad::create([
            'type' => 'checklist_item_added',
            'description' => 'añadió "' . $item->name . '" al checklist "' . $checklist->name . '"',
            'tarea_id' => $tareaId,
            'user_id' => auth()->id()
        ]);

        Log::info("✅ Item añadido con ID: {$item->id}");

        return response()->json([
            'message' => 200,
            'item' => $item,
            'progress' => $checklist->progress
        ], 201);
    }

    /**
     * Actualizar un item del checklist
     */
    public function updateItem(Request $request, $tareaId, $checklistId, $itemId)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'completed' => 'sometimes|required|boolean'
        ]);

        Log::info("✏️ Actualizando item ID: {$itemId}");

        $checklist = Checklist::where('tarea_id', $tareaId)
            ->where('id', $checklistId)
            ->firstOrFail();

        $item = ChecklistItem::where('checklist_id', $checklistId)
            ->where('id', $itemId)
            ->firstOrFail();

        $oldCompleted = $item->completed;

        $item->update($request->only(['name', 'completed']));

        // Registrar actividad solo si cambió el estado de completed
        if ($request->has('completed') && $oldCompleted !== $request->completed) {
            $action = $request->completed ? 'completó' : 'descompletó';
            Actividad::create([
                'type' => 'checklist_item_updated',
                'description' => $action . ' "' . $item->name . '" en el checklist "' . $checklist->name . '"',
                'tarea_id' => $tareaId,
                'user_id' => auth()->id()
            ]);
        }

        Log::info("✅ Item actualizado");

        return response()->json([
            'message' => 200,
            'item' => $item,
            'progress' => $checklist->fresh()->progress
        ]);
    }

    /**
     * Eliminar un item del checklist
     */
    public function destroyItem($tareaId, $checklistId, $itemId)
    {
        Log::info("🗑️ Eliminando item ID: {$itemId}");

        $checklist = Checklist::where('tarea_id', $tareaId)
            ->where('id', $checklistId)
            ->firstOrFail();

        $item = ChecklistItem::where('checklist_id', $checklistId)
            ->where('id', $itemId)
            ->firstOrFail();

        $itemName = $item->name;

        $item->delete();

        // Registrar actividad
        Actividad::create([
            'type' => 'checklist_item_deleted',
            'description' => 'eliminó "' . $itemName . '" del checklist "' . $checklist->name . '"',
            'tarea_id' => $tareaId,
            'user_id' => auth()->id()
        ]);

        Log::info("✅ Item eliminado");

        return response()->json([
            'message' => 200,
            'message_text' => 'Item eliminado exitosamente',
            'progress' => $checklist->fresh()->progress
        ]);
    }
}