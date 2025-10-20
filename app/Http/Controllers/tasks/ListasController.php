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
        $grupo_id = $request->get('grupo_id');
        
        $query = Lista::with('tareas');
        
        if ($grupo_id) {
            $query->where('grupo_id', $grupo_id);
        }
        
        // ✅ Ordenar por el campo 'orden'
        $listas = $query->orderBy('orden', 'asc')->get();
        
        return response()->json([
            'message' => 200,
            'listas' => $listas
        ]);
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