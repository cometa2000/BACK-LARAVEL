<?php

namespace App\Http\Controllers\tasks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\tasks\Checklist;
use App\Models\tasks\ChecklistItem;
use App\Models\tasks\Actividad;
use App\Models\tasks\Tareas;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ChecklistItemAsignadoAsignadorMail;
use App\Mail\ChecklistItemAsignadoAsignadoMail;

class ChecklistsController extends Controller
{
    /**
     * Obtener todos los checklists de una tarea
     */
    public function index($tareaId)
    {
        Log::info("📥 Obteniendo checklists de la tarea ID: {$tareaId}");
        
        $checklists = Checklist::with(['items.assignedUsers'])
            ->where('tarea_id', $tareaId)
            ->orderBy('orden', 'asc')
            ->get()
            ->map(function($checklist) {
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
            'name' => 'required|string|max:255',
            'due_date' => 'nullable|date',
            'assigned_users' => 'nullable|array',
            'assigned_users.*' => 'exists:users,id'
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
            'orden' => $maxOrden + 1,
            'due_date' => $request->due_date
        ]);

        // Asignar usuarios si se proporcionaron
        if ($request->has('assigned_users') && is_array($request->assigned_users)) {
            $item->assignedUsers()->sync($request->assigned_users);
        }

        // Cargar la relación para la respuesta
        $item->load('assignedUsers');

        if ($request->has('assigned_users') && !empty($request->assigned_users)) {
            $tarea     = \App\Models\tasks\Tareas::find($checklistId ? $checklist->tarea_id : $tareaId);
            $grupo     = $tarea ? \App\Models\tasks\Grupos::find($tarea->grupo_id) : null;
            $asignador = auth()->user();
 
            if ($tarea && $grupo) {
                $usuariosAsignadosData = $item->assignedUsers
                    ->map(fn($u) => ['name' => $u->name, 'email' => $u->email])
                    ->values()
                    ->toArray();
 
                // ✉️ Asignador
                try {
                    Mail::to($asignador->email)->send(new ChecklistItemAsignadoAsignadorMail(
                        $asignador->name,
                        $usuariosAsignadosData,
                        $item->name,
                        $checklist->name,
                        $tarea->name,
                        $grupo->name,
                        $grupo->id
                    ));
                } catch (\Exception $mailEx) {
                    Log::error('❌ Error ChecklistItemAsignadoAsignadorMail (addItem)', ['error' => $mailEx->getMessage()]);
                }
 
                // ✉️ Cada asignado
                foreach ($item->assignedUsers as $asignado) {
                    try {
                        Mail::to($asignado->email)->send(new ChecklistItemAsignadoAsignadoMail(
                            $asignado->name,
                            $asignador->name,
                            $item->name,
                            $checklist->name,
                            $tarea->name,
                            $grupo->name,
                            $grupo->id,
                            $item->due_date ? $item->due_date->format('Y-m-d') : null
                        ));
                    } catch (\Exception $mailEx) {
                        Log::error('❌ Error ChecklistItemAsignadoAsignadoMail (addItem)', ['error' => $mailEx->getMessage(), 'user_id' => $asignado->id]);
                    }
                }
            }
        }

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
            'item' => [
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
                })
            ],
            'progress' => $checklist->fresh()->progress
        ], 201);
    }

    /**
     * ✅ CORRECCIÓN CRÍTICA: Actualizar un item del checklist
     * Ahora incluye assigned_users en la respuesta
     */
    public function updateItem(Request $request, $tareaId, $checklistId, $itemId)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'completed' => 'sometimes|boolean',
            'due_date' => 'nullable|date',
            'assigned_users' => 'nullable|array',
            'assigned_users.*' => 'exists:users,id'
        ]);

        Log::info("✏️ Actualizando item ID: {$itemId}");
        Log::info("📥 Datos recibidos: " . json_encode($request->all()));

        $checklist = Checklist::where('tarea_id', $tareaId)
            ->where('id', $checklistId)
            ->firstOrFail();

        $item = ChecklistItem::where('checklist_id', $checklistId)
            ->where('id', $itemId)
            ->firstOrFail();

        $wasCompleted = $item->completed;
        $oldName = $item->name;

        // Actualizar campos básicos
        if ($request->has('name')) {
            $item->name = $request->name;
        }

        if ($request->has('completed')) {
            $item->completed = $request->completed;
        }

        if ($request->has('due_date')) {
            $item->due_date = $request->due_date;
        }

        $item->save();

        // ✅ CORRECCIÓN CRÍTICA: Sincronizar usuarios asignados si se proporcionaron
        if ($request->has('assigned_users')) {
            if (is_array($request->assigned_users)) {
                $item->assignedUsers()->sync($request->assigned_users);
                Log::info("✅ Usuarios sincronizados: " . json_encode($request->assigned_users));
            }
        }

        // ✅ CORRECCIÓN CRÍTICA: Cargar la relación para la respuesta
        $item->load('assignedUsers');
        
        Log::info("✅ Item actualizado con assigned_users: " . json_encode($item->assignedUsers));

        // Registrar actividad según el cambio
        if ($request->has('completed') && $wasCompleted != $item->completed) {
            $status = $item->completed ? 'completó' : 'desmarcó';
            Actividad::create([
                'type' => 'checklist_item_updated',
                'description' => $status . ' "' . $item->name . '" en el checklist "' . $checklist->name . '"',
                'tarea_id' => $tareaId,
                'user_id' => auth()->id()
            ]);
        } elseif ($request->has('name') && $oldName != $item->name) {
            Actividad::create([
                'type' => 'checklist_item_updated',
                'description' => 'renombró el elemento "' . $oldName . '" a "' . $item->name . '" en el checklist "' . $checklist->name . '"',
                'tarea_id' => $tareaId,
                'user_id' => auth()->id()
            ]);
        }

        Log::info("✅ Item actualizado exitosamente");

        // ✅ CORRECCIÓN CRÍTICA: Devolver item con assigned_users incluidos
        return response()->json([
            'message' => 200,
            'item' => [
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
            ],
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

    /**
     * Asignar miembros a un item específico
     */
    public function assignMembers(Request $request, $tareaId, $checklistId, $itemId)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        Log::info("👥 Asignando miembros al item ID: {$itemId}");

        $checklist = Checklist::where('tarea_id', $tareaId)
            ->where('id', $checklistId)
            ->firstOrFail();

        $item = ChecklistItem::where('checklist_id', $checklistId)
            ->where('id', $itemId)
            ->firstOrFail();

        // Guardar asignados PREVIOS para detectar los nuevos
        $previosIds = $item->assignedUsers()->pluck('users.id')->toArray();
 
        // Sincronizar usuarios (esto reemplaza los existentes)
        $item->assignedUsers()->sync($request->user_ids);
 
        // Cargar usuarios para la respuesta
        $item->load('assignedUsers');
 
        // IDs recién agregados (no estaban antes)
        $nuevosIds = array_diff($request->user_ids, $previosIds);
 
        if (!empty($nuevosIds)) {
            // Contexto completo para los correos
            $tarea  = \App\Models\tasks\Tareas::find($checklist->tarea_id);
            $grupo  = $tarea ? \App\Models\tasks\Grupos::find($tarea->grupo_id) : null;
            $asignador = auth()->user();
 
            if ($tarea && $grupo) {
                $usuariosAsignadosData = $item->assignedUsers
                    ->whereIn('id', $nuevosIds)
                    ->map(fn($u) => ['name' => $u->name, 'email' => $u->email])
                    ->values()
                    ->toArray();
 
                // ✉️ Correo al ASIGNADOR (resumen de quiénes fueron asignados)
                try {
                    Mail::to($asignador->email)->send(new ChecklistItemAsignadoAsignadorMail(
                        $asignador->name,
                        $usuariosAsignadosData,
                        $item->name,
                        $checklist->name,
                        $tarea->name,
                        $grupo->name,
                        $grupo->id
                    ));
                    Log::info('📧 ChecklistItemAsignadoAsignadorMail enviado', ['asignador_id' => $asignador->id]);
                } catch (\Exception $mailEx) {
                    Log::error('❌ Error ChecklistItemAsignadoAsignadorMail', ['error' => $mailEx->getMessage()]);
                }
 
                // ✉️ Correo individual a cada ASIGNADO (solo los nuevos)
                $nuevosUsuarios = $item->assignedUsers->whereIn('id', $nuevosIds);
                foreach ($nuevosUsuarios as $asignado) {
                    try {
                        Mail::to($asignado->email)->send(new ChecklistItemAsignadoAsignadoMail(
                            $asignado->name,
                            $asignador->name,
                            $item->name,
                            $checklist->name,
                            $tarea->name,
                            $grupo->name,
                            $grupo->id,
                            $item->due_date ? $item->due_date->format('Y-m-d') : null
                        ));
                        Log::info('📧 ChecklistItemAsignadoAsignadoMail enviado', ['asignado_id' => $asignado->id]);
                    } catch (\Exception $mailEx) {
                        Log::error('❌ Error ChecklistItemAsignadoAsignadoMail', ['error' => $mailEx->getMessage(), 'user_id' => $asignado->id]);
                    }
                }
            }
        }

        // Registrar actividad
        $userNames = User::whereIn('id', $request->user_ids)->pluck('name')->toArray();
        Actividad::create([
            'type' => 'checklist_item_members_assigned',
            'description' => 'asignó a ' . implode(', ', $userNames) . ' en "' . $item->name . '"',
            'tarea_id' => $tareaId,
            'user_id' => auth()->id()
        ]);

        Log::info("✅ Miembros asignados al item");

        return response()->json([
            'message' => 200,
            'assigned_users' => $item->assignedUsers->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'email' => $user->email,
                    'avatar' => $user->avatar ? $user->avatar : null
                ];
            })
        ]);
    }

    /**
     * Obtener miembros de un item
     */
    public function getMembers($tareaId, $checklistId, $itemId)
    {
        $item = ChecklistItem::where('checklist_id', $checklistId)
            ->where('id', $itemId)
            ->with('assignedUsers')
            ->firstOrFail();

        return response()->json([
            'message' => 200,
            'assigned_users' => $item->assignedUsers->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'email' => $user->email,
                    'avatar' => $user->avatar ? $user->avatar : null
                ];
            })
        ]);
    }

    /**
     * Desasignar un miembro de un item
     */
    public function unassignMember($tareaId, $checklistId, $itemId, $userId)
    {
        Log::info("➖ Desasignando usuario ID: {$userId} del item ID: {$itemId}");

        $checklist = Checklist::where('tarea_id', $tareaId)
            ->where('id', $checklistId)
            ->firstOrFail();

        $item = ChecklistItem::where('checklist_id', $checklistId)
            ->where('id', $itemId)
            ->firstOrFail();

        $user = User::findOrFail($userId);

        // Desasignar usuario
        $item->assignedUsers()->detach($userId);

        // Registrar actividad
        Actividad::create([
            'type' => 'checklist_item_member_unassigned',
            'description' => 'desasignó a ' . $user->name . ' de "' . $item->name . '"',
            'tarea_id' => $tareaId,
            'user_id' => auth()->id()
        ]);

        Log::info("✅ Miembro desasignado del item");

        return response()->json([
            'message' => 200,
            'message_text' => 'Miembro desasignado exitosamente'
        ]);
    }

    /**
     * 🆕 Obtener todos los checklists del grupo (para copiar)
     */
    public function getGroupChecklists($grupoId)
    {
        Log::info("📥 Obteniendo checklists del grupo ID: {$grupoId}");
        
        try {
            // ✅ ESTRATEGIA 1: Intentar búsqueda directa con grupo_id (más rápido)
            $checklists = Checklist::with(['tarea'])
                ->whereHas('tarea', function($query) use ($grupoId) {
                    $query->where('grupo_id', $grupoId)
                        ->whereNull('deleted_at');
                })
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info("✅ Checklists encontrados con grupo_id directo: " . $checklists->count());

            // ✅ ESTRATEGIA 2: Si no encontró nada, intentar con JOIN a listas (fallback)
            if ($checklists->isEmpty()) {
                Log::info("⚠️ No se encontraron checklists con grupo_id directo, intentando con JOIN a listas");
                
                $checklists = Checklist::with(['tarea.lista'])
                    ->whereHas('tarea.lista', function($query) use ($grupoId) {
                        $query->where('grupo_id', $grupoId);
                    })
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'desc')
                    ->get();

                Log::info("✅ Checklists encontrados con JOIN a listas: " . $checklists->count());
            }

            // Formatear checklists para la respuesta
            $checklistsFormateados = $checklists->map(function($checklist) {
                $itemsCount = ChecklistItem::where('checklist_id', $checklist->id)
                    ->whereNull('deleted_at')
                    ->count();
                
                // Obtener nombre de la tarea de forma segura
                $tareaName = 'Sin tarea';
                if ($checklist->tarea) {
                    $tareaName = $checklist->tarea->name;
                }
                
                return [
                    'id' => $checklist->id,
                    'name' => $checklist->name,
                    'tarea_id' => $checklist->tarea_id,
                    'tarea_name' => $tareaName,
                    'items_count' => $itemsCount,
                    'display_name' => $checklist->name . ' (' . $tareaName . ' - ' . $itemsCount . ' elementos)'
                ];
            });

            Log::info("✅ Total de checklists formateados: " . $checklistsFormateados->count());

            return response()->json([
                'message' => 200,
                'checklists' => $checklistsFormateados
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Error al obtener checklists del grupo", [
                'grupo_id' => $grupoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 500,
                'error' => 'Error al obtener checklists del grupo',
                'checklists' => []
            ], 500);
        }
    }

    /**
     * 🆕 Copiar un checklist existente a una nueva tarea
     */
    public function copyChecklist(Request $request, $tareaId)
    {
        $request->validate([
            'source_checklist_id' => 'required|exists:checklists,id'
        ]);

        Log::info("📋 Copiando checklist ID: {$request->source_checklist_id} a tarea ID: {$tareaId}");

        // Obtener el checklist original con sus items
        $sourceChecklist = Checklist::with('items')->findOrFail($request->source_checklist_id);

        // Obtener el siguiente orden
        $maxOrden = Checklist::where('tarea_id', $tareaId)->max('orden') ?? 0;

        // Crear el nuevo checklist
        $newChecklist = Checklist::create([
            'name' => $sourceChecklist->name,
            'tarea_id' => $tareaId,
            'orden' => $maxOrden + 1
        ]);

        // Copiar todos los items (sin marcarlos como completados)
        foreach ($sourceChecklist->items as $index => $sourceItem) {
            ChecklistItem::create([
                'name' => $sourceItem->name,
                'checklist_id' => $newChecklist->id,
                'orden' => $index + 1,
                'completed' => false, // Siempre sin completar
                'due_date' => null // Sin fecha
            ]);
        }

        // Registrar actividad
        Actividad::create([
            'type' => 'checklist_copied',
            'description' => 'copió el checklist "' . $newChecklist->name . '" con ' . $sourceChecklist->items->count() . ' elementos',
            'tarea_id' => $tareaId,
            'user_id' => auth()->id()
        ]);

        // Cargar el checklist con sus items para la respuesta
        $newChecklist->load('items');

        Log::info("✅ Checklist copiado exitosamente con ID: {$newChecklist->id}");

        return response()->json([
            'message' => 200,
            'checklist' => [
                'id' => $newChecklist->id,
                'name' => $newChecklist->name,
                'orden' => $newChecklist->orden,
                'progress' => 0,
                'items' => $newChecklist->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'completed' => $item->completed,
                        'orden' => $item->orden,
                        'due_date' => null,
                        'assigned_users' => [],
                        'is_overdue' => false,
                        'is_due_soon' => false
                    ];
                })
            ]
        ], 201);
    }


    /**
     * 🆕 Reordenar checklists de una tarea
     */
    public function reorder(Request $request, $tareaId)
    {
        $request->validate([
            'checklists' => 'required|array',
            'checklists.*.id' => 'required|integer|exists:checklists,id',
            'checklists.*.orden' => 'required|integer|min:0',
        ]);

        Log::info("🔀 Reordenando checklists de la tarea ID: {$tareaId}");

        foreach ($request->checklists as $item) {
            Checklist::where('id', $item['id'])
                ->where('tarea_id', $tareaId)
                ->update(['orden' => $item['orden']]);
        }

        Log::info("✅ Checklists reordenados");

        return response()->json([
            'message' => 200,
            'message_text' => 'Orden actualizado exitosamente'
        ]);
    }
}