<?php

namespace App\Http\Controllers\tasks;

use App\Models\tasks\Lista;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class ListasController extends Controller
{

    public function index(Request $request)
    {
        try {
            $grupo_id = $request->get('grupo_id');
            
            Log::info('📋 Cargando listas', ['grupo_id' => $grupo_id]);
            
            $query = Lista::with([
                'tareas.etiquetas',
                'tareas.adjuntos',
                'tareas.checklists.items',
                'tareas.user',
                'tareas.comentarios',
                'tareas.assignedUsers'  // 🆕 AGREGADO: Cargar miembros asignados
            ]);
            
            if ($grupo_id) {
                $query->where('grupo_id', $grupo_id);
            }
            
            $listas = $query->orderBy('orden', 'asc')->get();
            
            // 🆕 Convertir a array y formatear
            $listasArray = $listas->toArray();
            
            // Formatear solo los adjuntos
            foreach ($listasArray as &$lista) {
                if (isset($lista['tareas'])) {
                    foreach ($lista['tareas'] as &$tarea) {
                        $enlaces = [];
                        $archivos = [];
                        
                        // Procesar adjuntos si existen
                        if (isset($tarea['adjuntos']) && is_array($tarea['adjuntos'])) {
                            foreach ($tarea['adjuntos'] as $adjunto) {
                                if ($adjunto['tipo'] === 'enlace') {
                                    $enlaces[] = [
                                        'id' => $adjunto['id'],
                                        'url' => $adjunto['url'],
                                        'nombre' => $adjunto['nombre'],
                                    ];
                                } elseif ($adjunto['tipo'] === 'archivo') {
                                    $archivos[] = [
                                        'id' => $adjunto['id'],
                                        'nombre' => $adjunto['nombre'],
                                        'tipo' => $adjunto['mime_type'] ?? 'unknown',
                                        'tiempo_subida' => $adjunto['created_at'] ?? null,
                                        'preview' => $adjunto['preview'] ?? null,
                                        'file_url' => isset($adjunto['file_path']) 
                                            ? url('storage/' . $adjunto['file_path']) 
                                            : null
                                    ];
                                }
                            }
                        }
                        
                        // Reemplazar adjuntos con el formato correcto
                        $tarea['adjuntos'] = [
                            'enlaces' => $enlaces,
                            'archivos' => $archivos
                        ];
                        
                        // 🆕 AGREGADO: Formatear miembros asignados
                        if (isset($tarea['assigned_users']) && is_array($tarea['assigned_users'])) {
                            $tarea['assigned_members'] = array_map(function($user) {
                                return [
                                    'id' => $user['id'],
                                    'name' => $user['name'],
                                    'surname' => $user['surname'] ?? '',
                                    'email' => $user['email'],
                                    'avatar' => isset($user['avatar']) && $user['avatar'] 
                                        ? url('storage/' . $user['avatar']) 
                                        : null,
                                ];
                            }, $tarea['assigned_users']);
                            unset($tarea['assigned_users']); // Eliminar el campo original
                        } else {
                            $tarea['assigned_members'] = [];
                        }
                        
                        // Agregar contador de comentarios
                        $tarea['comentarios_count'] = isset($tarea['comentarios']) 
                            ? count($tarea['comentarios']) 
                            : 0;
                    }
                }
            }
            
            Log::info('✅ Listas formateadas', ['count' => count($listasArray)]);
            
            return response()->json([
                'message' => 200,
                'listas' => $listasArray
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error:', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 500,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'grupo_id' => 'required|exists:grupos,id'
        ]);

        // ✅ Obtener el orden máximo actual y sumar 1
        $maxOrden = Lista::where('grupo_id', $request->grupo_id)->max('orden') ?? -1;

        $lista = Lista::create([
            'name' => $request->name,
            'grupo_id' => $request->grupo_id,
            'orden' => $maxOrden + 1,
        ]);

        return response()->json([
            'message' => 200,
            'lista' => $lista,
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $lista = Lista::findOrFail($id);
            $lista->update($request->only('name'));
            return response()->json(['message' => 200, 'lista' => $lista]);
        } catch (\Exception $e) {
            return response()->json(['message' => 500, 'error' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $lista = Lista::findOrFail($id);
        $lista->delete();
        return response()->json(['message' => 200]);
    }

    // ✅ NUEVO: Reordenar listas
    public function reorder(Request $request)
    {
        $request->validate([
            'listas' => 'required|array',
            'listas.*.id' => 'required|exists:listas,id',
            'listas.*.orden' => 'required|integer'
        ]);

        try {
            foreach ($request->listas as $listaData) {
                Lista::where('id', $listaData['id'])
                    ->update(['orden' => $listaData['orden']]);
            }

            Log::info('✅ Listas reordenadas correctamente');

            return response()->json([
                'message' => 200,
                'listas' => Lista::whereIn('id', array_column($request->listas, 'id'))
                    ->orderBy('orden', 'asc')
                    ->get()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al reordenar listas: ' . $e->getMessage());
            return response()->json([
                'message' => 500,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}