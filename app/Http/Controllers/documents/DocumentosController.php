<?php

namespace App\Http\Controllers\documents;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Models\documents\Documentos;
use App\Models\Configuration\Sucursale;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DocumentosController extends Controller
{
    /**
     * Display a listing of the resource with tree structure
     */
    public function index(Request $request)
    {
        $search = $request->get("search");
        $sucursale_id = $request->get("sucursale_id");
        $parent_id = $request->get("parent_id", null); // null = raíz

        $query = Documentos::with(['user', 'sucursale'])
            ->where(function($q) use ($search) {
                if ($search) {
                    $q->where("name", "like", "%" . $search . "%");
                }
            });

        // Filtrar por sucursal si se proporciona
        if ($sucursale_id) {
            $query->where('sucursale_id', $sucursale_id);
        }

        // Filtrar por carpeta padre
        if ($parent_id === 'null' || $parent_id === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parent_id);
        }

        $documentos = $query->orderBy('type', 'desc') // Carpetas primero
                           ->orderBy('order', 'asc')
                           ->orderBy('name', 'asc')
                           ->paginate(50);

        return response()->json([
            "total" => $documentos->total(),
            "documentos" => $documentos->map(function($documento) {
                return $this->formatDocumento($documento);
            }),
        ]);
    }

    /**
     * Get tree structure for a specific sucursal
     */
    public function getTree(Request $request)
    {
        $sucursale_id = $request->get("sucursale_id");
        
        if (!$sucursale_id) {
            return response()->json([
                "message" => 403,
                "message_text" => "Se requiere sucursale_id"
            ], 403);
        }

        // Obtener todos los elementos de la raíz (sin parent_id)
        $rootItems = Documentos::with(['allChildren.user', 'user', 'sucursale'])
            ->where('sucursale_id', $sucursale_id)
            ->whereNull('parent_id')
            ->orderBy('type', 'desc') // Carpetas primero
            ->orderBy('order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            "tree" => $rootItems->map(function($item) {
                return $this->formatDocumentoTree($item);
            }),
        ]);
    }

    /**
     * Get contents of a specific folder
     */
    public function getFolderContents($folderId)
    {
        $folder = Documentos::with(['children.user', 'parent'])->find($folderId);

        if (!$folder) {
            return response()->json([
                "message" => 404,
                "message_text" => "Carpeta no encontrada"
            ], 404);
        }

        if (!$folder->isFolder()) {
            return response()->json([
                "message" => 403,
                "message_text" => "El elemento no es una carpeta"
            ], 403);
        }

        return response()->json([
            "folder" => $this->formatDocumento($folder),
            "path" => $folder->getPath(),
            "contents" => $folder->children->map(function($item) {
                return $this->formatDocumento($item);
            }),
        ]);
    }

    /**
     * Create a new folder
     */
    public function createFolder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sucursale_id' => 'required|exists:sucursales,id',
            'parent_id' => 'nullable|exists:documentos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => 403,
                "message_text" => $validator->errors()->first()
            ], 403);
        }

        // Verificar que no exista una carpeta con el mismo nombre en la misma ubicación
        $exists = Documentos::where('name', $request->name)
            ->where('sucursale_id', $request->sucursale_id)
            ->where('type', 'folder')
            ->where(function($q) use ($request) {
                if ($request->parent_id) {
                    $q->where('parent_id', $request->parent_id);
                } else {
                    $q->whereNull('parent_id');
                }
            })
            ->first();

        if ($exists) {
            return response()->json([
                "message" => 403,
                "message_text" => "Ya existe una carpeta con ese nombre en esta ubicación"
            ], 403);
        }

        // Verificar que el parent_id sea una carpeta válida
        if ($request->parent_id) {
            $parent = Documentos::find($request->parent_id);
            if (!$parent || !$parent->isFolder()) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "El padre debe ser una carpeta válida"
                ], 403);
            }
        }

        $folder = Documentos::create([
            "name" => $request->name,
            "type" => "folder",
            "parent_id" => $request->parent_id,
            "sucursale_id" => $request->sucursale_id,
            "user_id" => auth()->id(),
            "description" => $request->description,
            "order" => $request->order ?? 0,
        ]);

        return response()->json([
            "message" => 200,
            "message_text" => "Carpeta creada exitosamente",
            "folder" => $this->formatDocumento($folder),
        ]);
    }

    /**
     * Move document/folder to another location (drag and drop)
     */
    public function move(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'parent_id' => 'nullable|exists:documentos,id',
            'order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => 403,
                "message_text" => $validator->errors()->first()
            ], 403);
        }

        $documento = Documentos::findOrFail($id);

        // Verificar que se puede mover a la ubicación destino
        if ($request->has('parent_id') && $request->parent_id !== null) {
            $targetParent = Documentos::find($request->parent_id);
            
            if (!$targetParent) {
                return response()->json([
                    "message" => 404,
                    "message_text" => "Carpeta destino no encontrada"
                ], 404);
            }

            if (!$targetParent->isFolder()) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "El destino debe ser una carpeta"
                ], 403);
            }

            // Verificar que no se mueva una carpeta dentro de sí misma
            if (!$documento->canMoveTo($request->parent_id)) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "No se puede mover una carpeta dentro de sí misma o de sus subcarpetas"
                ], 403);
            }
        }

        // Actualizar la posición
        $documento->parent_id = $request->parent_id;
        
        if ($request->has('order')) {
            $documento->order = $request->order;
        }
        
        $documento->save();

        return response()->json([
            "message" => 200,
            "message_text" => "Elemento movido exitosamente",
            "documento" => $this->formatDocumento($documento),
        ]);
    }

    /**
     * Upload file (puede ser a raíz o dentro de una carpeta)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sucursale_id' => 'required|exists:sucursales,id',
            'parent_id' => 'nullable|exists:documentos,id',
            'file' => 'required|file|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => 403,
                "message_text" => $validator->errors()->first()
            ], 403);
        }

        // Verificar que el parent_id sea una carpeta si se proporciona
        if ($request->parent_id) {
            $parent = Documentos::find($request->parent_id);
            if (!$parent || !$parent->isFolder()) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "El padre debe ser una carpeta válida"
                ], 403);
            }
        }

        $file_path = null;
        $mime_type = null;
        $size = null;

        if ($request->hasFile("file")) {
            $file = $request->file("file");
            $file_path = $file->store("documentos", "public");
            $mime_type = $file->getClientMimeType();
            $size = $file->getSize();
        }

        $documento = Documentos::create([
            "name" => $request->name,
            "type" => "file",
            "parent_id" => $request->parent_id,
            "sucursale_id" => $request->sucursale_id,
            "user_id" => auth()->id(),
            "file_path" => $file_path,
            "mime_type" => $mime_type,
            "size" => $size,
            "description" => $request->description,
            "order" => $request->order ?? 0,
        ]);

        return response()->json([
            "message" => 200,
            "message_text" => "Archivo subido exitosamente",
            "documento" => $this->formatDocumento($documento),
        ]);
    }

    /**
     * Get configuration data
     */
    public function config(){
        return response()->json([
            "roles" => Role::all(), 
            "sucursales" => Sucursale::all(),
        ]);
    }

    /**
     * Update the specified resource
     */
    public function update(Request $request, string $id)
    {
        $documento = Documentos::findOrFail($id);
        
        // Solo actualizar campos permitidos
        $allowedFields = ['name', 'description', 'order'];
        $updateData = $request->only($allowedFields);
        
        $documento->update($updateData);
        
        return response()->json([
            "message" => 200,
            "message_text" => "Actualizado exitosamente",
            "documento" => $this->formatDocumento($documento),
        ]);
    }

    /**
     * Remove the specified resource (incluyendo carpetas y su contenido)
     */
    public function destroy(string $id)
    {
        $documento = Documentos::findOrFail($id);
        
        if ($documento->isFolder()) {
            // Eliminar recursivamente todos los hijos
            $this->deleteFolderRecursive($documento);
        } else {
            // Eliminar archivo físico si existe
            if ($documento->file_path && Storage::disk('public')->exists($documento->file_path)) {
                Storage::disk('public')->delete($documento->file_path);
            }
        }
        
        $documento->delete();
        
        return response()->json([
            "message" => 200,
            "message_text" => "Eliminado exitosamente",
        ]);
    }

    /**
     * Eliminar carpeta y todo su contenido recursivamente
     */
    private function deleteFolderRecursive(Documentos $folder)
    {
        foreach ($folder->children as $child) {
            if ($child->isFolder()) {
                $this->deleteFolderRecursive($child);
            } else {
                // Eliminar archivo físico
                if ($child->file_path && Storage::disk('public')->exists($child->file_path)) {
                    Storage::disk('public')->delete($child->file_path);
                }
                $child->delete();
            }
        }
    }

    /**
     * Formatear documento para respuesta JSON
     */
    private function formatDocumento($documento)
    {
        return [
            "id" => $documento->id,
            "name" => $documento->name,
            "type" => $documento->type,
            "parent_id" => $documento->parent_id,
            "order" => $documento->order ?? 0,
            "sucursale_id" => $documento->sucursale_id,
            "sucursale" => $documento->sucursale,
            "user_id" => $documento->user_id,
            "user" => $documento->user ? [
                "id" => $documento->user->id,
                "name" => $documento->user->name,
                "surname" => $documento->user->surname ?? '',
                "email" => $documento->user->email,
            ] : null,
            "file_path" => $documento->file_path 
                ? env("APP_URL")."/storage/".$documento->file_path 
                : null,
            "mime_type" => $documento->mime_type,
            "size" => $documento->size,
            "size_formatted" => $documento->size ? $this->formatBytes($documento->size) : null,
            "description" => $documento->description,
            "children_count" => $documento->isFolder() ? $documento->children->count() : 0,
            "files_count" => $documento->isFolder() ? $documento->countAllFiles() : 0,
            "created_at" => $documento->created_at->format("Y-m-d h:i A"),
            "updated_at" => $documento->updated_at->format("Y-m-d h:i A"),
        ];
    }

    /**
     * Formatear documento con hijos para árbol
     */
    private function formatDocumentoTree($documento)
    {
        $formatted = $this->formatDocumento($documento);
        
        if ($documento->isFolder() && $documento->relationLoaded('allChildren')) {
            $formatted['children'] = $documento->allChildren->map(function($child) {
                return $this->formatDocumentoTree($child);
            });
        }
        
        return $formatted;
    }

    /**
     * Formatear bytes a tamaño legible
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Descargar archivo
     */
    public function download($id)
    {
        $documento = Documentos::findOrFail($id);
        
        if ($documento->isFolder()) {
            return response()->json([
                "message" => 403,
                "message_text" => "No se puede descargar una carpeta"
            ], 403);
        }
        
        if (!$documento->file_path) {
            return response()->json([
                "message" => 404,
                "message_text" => "Archivo no encontrado"
            ], 404);
        }
        
        $filePath = storage_path('app/public/' . $documento->file_path);
        
        if (!file_exists($filePath)) {
            return response()->json([
                "message" => 404,
                "message_text" => "Archivo físico no encontrado"
            ], 404);
        }
        
        return response()->download($filePath, $documento->name);
    }

    /**
     * Obtener información del documento para el visor
     */
    public function getDocumentInfo($id)
    {
        $documento = Documentos::findOrFail($id);
        
        if ($documento->isFolder()) {
            return response()->json([
                "message" => 403,
                "message_text" => "No se puede visualizar una carpeta"
            ], 403);
        }
        
        $canEdit = $this->canEditDocument($documento);
        $documentType = $this->getDocumentType($documento->mime_type);
        
        return response()->json([
            "id" => $documento->id,
            "name" => $documento->name,
            "url" => env("APP_URL")."/storage/".$documento->file_path,
            "mime_type" => $documento->mime_type,
            "document_type" => $documentType,
            "can_edit" => $canEdit,
            "user" => $documento->user,
            "created_at" => $documento->created_at->format("Y-m-d h:i A"),
        ]);
    }

    private function canEditDocument($documento)
    {
        $editableMimes = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-powerpoint',
            'text/plain',
        ];
        
        return in_array($documento->mime_type, $editableMimes);
    }

    private function getDocumentType($mimeType)
    {
        if (in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
            'text/plain'
        ])) {
            return 'text';
        }
        
        if (in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel'
        ])) {
            return 'spreadsheet';
        }
        
        if (in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-powerpoint'
        ])) {
            return 'presentation';
        }
        
        if ($mimeType === 'application/pdf') {
            return 'pdf';
        }
        
        if (strpos($mimeType, 'image/') === 0) {
            return 'image';
        }
        
        return 'unknown';
    }

    public function saveDocument(Request $request, $id)
    {
        $documento = Documentos::findOrFail($id);
        $status = $request->input('status');
        
        if ($status == 2 || $status == 3) {
            $downloadUrl = $request->input('url');
            
            if ($downloadUrl) {
                try {
                    $fileContents = file_get_contents($downloadUrl);
                    Storage::disk('public')->put($documento->file_path, $fileContents);
                    $documento->touch();
                    return response()->json(['error' => 0]);
                } catch (\Exception $e) {
                    Log::error('Error guardando documento: ' . $e->getMessage());
                    return response()->json(['error' => 1]);
                }
            }
        }
        
        return response()->json(['error' => 0]);
    }
}