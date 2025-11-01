<?php

namespace App\Http\Controllers\tasks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\tasks\TareaAdjunto;
use App\Models\tasks\Tareas;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TareaAdjuntosController extends Controller
{
    /**
     * Obtener adjuntos de una tarea
     */
    public function index($tareaId)
    {
        try {
            $tarea = Tareas::findOrFail($tareaId);
            $adjuntos = $tarea->adjuntos;

            $enlaces = $adjuntos->where('tipo', 'enlace')->values();
            $archivos = $adjuntos->where('tipo', 'archivo')->map(function($adjunto) {
                return [
                    'id' => $adjunto->id,
                    'nombre' => $adjunto->nombre,
                    'tipo' => $adjunto->mime_type,
                    'tiempo_subida' => $adjunto->created_at->toISOString(),
                    'preview' => $adjunto->preview,
                    'file_url' => $adjunto->file_path 
                        ? url('storage/' . $adjunto->file_path) 
                        : null
                ];
            })->values();

            return response()->json([
                'message' => 200,
                'adjuntos' => [
                    'enlaces' => $enlaces,
                    'archivos' => $archivos
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener adjuntos', [
                'tarea_id' => $tareaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 500,
                'error' => 'Error al obtener adjuntos'
            ], 500);
        }
    }

    /**
     * Guardar adjunto (archivo o enlace)
     */
    public function store(Request $request, $tareaId)
    {
        try {
            $tarea = Tareas::findOrFail($tareaId);

            $tipo = $request->input('tipo'); // 'archivo' o 'enlace'

            if ($tipo === 'enlace') {
                // Guardar enlace
                $adjunto = TareaAdjunto::create([
                    'tarea_id' => $tareaId,
                    'tipo' => 'enlace',
                    'nombre' => $request->input('nombre'),
                    'url' => $request->input('url')
                ]);

                return response()->json([
                    'message' => 200,
                    'adjunto' => $adjunto
                ]);

            } elseif ($tipo === 'archivo') {
                // Guardar archivo
                $request->validate([
                    'file' => 'required|file|max:10240' // Max 10MB
                ]);

                $file = $request->file('file');
                $path = $file->store('tarea_adjuntos', 'public');

                // Preview para imágenes
                $preview = null;
                if (str_starts_with($file->getMimeType(), 'image/')) {
                    $preview = base64_encode(file_get_contents($file->getRealPath()));
                }

                $adjunto = TareaAdjunto::create([
                    'tarea_id' => $tareaId,
                    'tipo' => 'archivo',
                    'nombre' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'preview' => $preview
                ]);

                return response()->json([
                    'message' => 200,
                    'adjunto' => [
                        'id' => $adjunto->id,
                        'nombre' => $adjunto->nombre,
                        'tipo' => $adjunto->mime_type,
                        'tiempo_subida' => $adjunto->created_at->toISOString(),
                        'preview' => $adjunto->preview,
                        'file_url' => url('storage/' . $adjunto->file_path)
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error al guardar adjunto', [
                'tarea_id' => $tareaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 500,
                'error' => 'Error al guardar adjunto'
            ], 500);
        }
    }

    /**
     * Eliminar adjunto
     */
    public function destroy($tareaId, $adjuntoId)
    {
        try {
            $adjunto = TareaAdjunto::where('tarea_id', $tareaId)
                ->findOrFail($adjuntoId);

            // Eliminar archivo físico si existe
            if ($adjunto->file_path) {
                Storage::disk('public')->delete($adjunto->file_path);
            }

            $adjunto->delete();

            return response()->json([
                'message' => 200,
                'success' => 'Adjunto eliminado'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar adjunto', [
                'tarea_id' => $tareaId,
                'adjunto_id' => $adjuntoId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 500,
                'error' => 'Error al eliminar adjunto'
            ], 500);
        }
    }
}