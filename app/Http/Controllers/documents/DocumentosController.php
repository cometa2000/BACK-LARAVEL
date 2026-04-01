<?php

namespace App\Http\Controllers\documents;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Models\documents\Documentos;
use App\Models\documents\DocumentoView;
use App\Models\Configuration\Sucursale;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DocumentosController extends Controller
{
    /**
     * Display a listing of the resource with tree structure
     */
    public function index(Request $request)
    {
        $search = $request->get("search");
        $sucursale_id = $request->get("sucursale_id");
        $parent_id = $request->get("parent_id", null);
        $user = auth()->user();

        $query = Documentos::with(['user', 'sucursale'])
            ->where(function($q) use ($search) {
                if ($search) {
                    $q->where("name", "like", "%" . $search . "%");
                }
            });

        // Filtrar por sucursal si se proporciona
        if ($sucursale_id) {
            $query->where('sucursale_id', $sucursale_id);
        } else {
            // Si no se especifica sucursal y el usuario NO es admin (rol_id != 1)
            // mostrar solo su sucursal
            if ($user->role_id != 1) {
                $query->where('sucursale_id', $user->sucursale_id);
            }
        }

        // Filtrar por carpeta padre
        if ($parent_id === 'null' || $parent_id === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parent_id);
        }

        $documentos = $query->orderBy('type', 'desc')
                           ->orderBy('order', 'asc')
                           ->orderBy('name', 'asc')
                           ->paginate(50);

        return response()->json([
            "total" => $documentos->total(),
            "documentos" => $documentos->map(function($documento) use ($user) {
                return $this->formatDocumento($documento, $user);
            }),
        ]);
    }

    /**
     * Get tree structure for a specific sucursal
     */
    public function getTree(Request $request)
    {
        $sucursale_id = $request->get("sucursale_id");
        $user = auth()->user();
        
        if (!$sucursale_id) {
            return response()->json([
                "message" => 403,
                "message_text" => "Se requiere sucursale_id"
            ], 403);
        }

        $rootItems = Documentos::with(['allChildren.user', 'user', 'sucursale'])
            ->where('sucursale_id', $sucursale_id)
            ->whereNull('parent_id')
            ->orderBy('type', 'desc')
            ->orderBy('order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            "tree" => $rootItems->map(function($item) use ($user) {
                return $this->formatDocumentoTree($item, $user);
            }),
        ]);
    }

    /**
     * Get folder tree for selection (para elegir dónde subir)
     */
    public function getFolderTree(Request $request)
    {
        $sucursale_id = $request->get("sucursale_id");
        
        if (!$sucursale_id) {
            return response()->json([
                "message" => 403,
                "message_text" => "Se requiere sucursale_id"
            ], 403);
        }

        // Solo obtener carpetas
        $folders = Documentos::with(['allChildren' => function($query) {
                $query->where('type', 'folder');
            }])
            ->where('sucursale_id', $sucursale_id)
            ->where('type', 'folder')
            ->whereNull('parent_id')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            "folders" => $folders->map(function($folder) {
                return $this->formatFolderForTree($folder);
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

        $user = auth()->user();

        return response()->json([
            "folder" => $this->formatDocumento($folder, $user),
            "path" => $folder->getPath(),
            "contents" => $folder->children->map(function($item) use ($user) {
                return $this->formatDocumento($item, $user);
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

        $user = auth()->user();

        return response()->json([
            "message" => 200,
            "message_text" => "Carpeta creada exitosamente",
            "folder" => $this->formatDocumento($folder, $user),
        ]);
    }

    /**
     * Store a newly created resource (archivo o múltiples archivos)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sucursale_ids' => 'required|array|min:1',
            'sucursale_ids.*' => 'exists:sucursales,id',
            'files' => 'required|array|min:1',
            'files.*' => 'file|max:20480',
            'parent_id' => 'nullable|exists:documentos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => 403,
                "message_text" => $validator->errors()->first()
            ], 403);
        }

        $user = auth()->user();
        $sucursales = $request->sucursale_ids;
        $files = $request->file('files');
        $parent_id = $request->parent_id;
        $description = $request->description;

        // Si hay más de una sucursal, parent_id debe ser null
        if (count($sucursales) > 1 && $parent_id !== null) {
            return response()->json([
                "message" => 403,
                "message_text" => "Al subir a múltiples sucursales, los archivos deben ir en la raíz"
            ], 403);
        }

        // Verificar que el parent_id sea una carpeta válida
        if ($parent_id) {
            $parent = Documentos::find($parent_id);
            if (!$parent || !$parent->isFolder()) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "El destino debe ser una carpeta válida"
                ], 403);
            }
        }

        $uploadedDocuments = [];

        DB::beginTransaction();
        try {
            foreach ($files as $file) {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                
                foreach ($sucursales as $sucursale_id) {
                    // Generar nombre único
                    $fileName = $this->generateUniqueFileName($originalName, $extension, $sucursale_id, $parent_id);
                    
                    // Guardar archivo
                    $path = $file->storeAs('documentos/' . $sucursale_id, $fileName, 'public');
                    
                    // Crear registro
                    $documento = Documentos::create([
                        "name" => $originalName . '.' . $extension,
                        "type" => "file",
                        "parent_id" => $parent_id,
                        "sucursale_id" => $sucursale_id,
                        "user_id" => $user->id,
                        "file_path" => $path,
                        "mime_type" => $file->getMimeType(),
                        "size" => $file->getSize(),
                        "description" => $description,
                    ]);
                    
                    $uploadedDocuments[] = $this->formatDocumento($documento, $user);
                }
            }
            
            DB::commit();
            
            return response()->json([
                "message" => 200,
                "message_text" => "Archivo(s) subido(s) exitosamente",
                "documentos" => $uploadedDocuments,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error uploading files: ' . $e->getMessage());
            
            return response()->json([
                "message" => 500,
                "message_text" => "Error al subir archivo(s): " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Move document/folder to another location
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

            if (!$documento->canMoveTo($request->parent_id)) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "No se puede mover una carpeta dentro de sí misma o de sus subcarpetas"
                ], 403);
            }
        }

        $documento->parent_id = $request->parent_id;
        
        if ($request->has('order')) {
            $documento->order = $request->order;
        }
        
        $documento->save();

        $user = auth()->user();

        return response()->json([
            "message" => 200,
            "message_text" => "Elemento movido exitosamente",
            "documento" => $this->formatDocumento($documento, $user),
        ]);
    }

    /**
     * Update document/folder
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => 403,
                "message_text" => $validator->errors()->first()
            ], 403);
        }

        $documento = Documentos::findOrFail($id);

        if ($request->has('name')) {
            $documento->name = $request->name;
        }

        if ($request->has('description')) {
            $documento->description = $request->description;
        }

        $documento->save();

        $user = auth()->user();

        return response()->json([
            "message" => 200,
            "message_text" => "Actualizado exitosamente",
            "documento" => $this->formatDocumento($documento, $user),
        ]);
    }

    /**
     * Marcar documento como visto
     */
    public function markAsViewed(Request $request, $id)
    {
        $user = auth()->user();
        $documento = Documentos::findOrFail($id);

        // Solo marcar archivos, no carpetas
        if ($documento->isFile()) {
            DocumentoView::updateOrCreate(
                [
                    'documento_id' => $id,
                    'user_id' => $user->id,
                ],
                [
                    'viewed_at' => now(),
                ]
            );
        }

        return response()->json([
            "message" => 200,
            "message_text" => "Documento marcado como visto"
        ]);
    }

    /**
     * Remove the specified resource
     */
    public function destroy(string $id)
    {
        $documento = Documentos::findOrFail($id);
        $hasChildren = false;
        $movedCount = 0;
        
        if ($documento->isFolder()) {
            // Verificar si tiene contenido
            $children = $documento->children;
            $hasChildren = $children->count() > 0;
            
            if ($hasChildren) {
                // Mover todos los hijos a la raíz (parent_id = null)
                foreach ($children as $child) {
                    $child->parent_id = null;
                    $child->save();
                    $movedCount++;
                }
            }
        } else {
            // Eliminar archivo físico si existe
            if ($documento->file_path && Storage::disk('public')->exists($documento->file_path)) {
                Storage::disk('public')->delete($documento->file_path);
            }
        }
        
        $documento->delete();
        
        $message = "Eliminado exitosamente";
        if ($hasChildren) {
            $message = "Carpeta eliminada. Se movieron {$movedCount} elemento(s) a la raíz.";
        }
        
        return response()->json([
            "message" => 200,
            "message_text" => $message,
            "moved_children" => $hasChildren,
            "moved_count" => $movedCount,
        ]);
    }

    /**
     * Get configuration data
     */
    public function config()
    {
        $user = auth()->user();
        
        if ($user->role_id == 1) {
            // Admin ve todas las sucursales
            $sucursales = Sucursale::where('state', 1)->get();
        } else {
            // Usuario normal ve solo su sucursal
            $sucursales = Sucursale::where('id', $user->sucursale_id)
                                  ->where('state', 1)
                                  ->get();
        }

        // Agregar estadísticas recursivas a cada sucursal
        $sucursales = $sucursales->map(function($sucursal) {
            $stats = $this->getSucursalStats($sucursal->id);
            return [
                'id' => $sucursal->id,
                'name' => $sucursal->name,
                'address' => $sucursal->address,
                'state' => $sucursal->state,
                'totalDocs' => $stats['totalDocs'],
                'totalFolders' => $stats['totalFolders'],
                'totalFiles' => $stats['totalFiles'],
            ];
        });

        return response()->json([
            "sucursales" => $sucursales,
            "user" => [
                "id" => $user->id,
                "role_id" => $user->role_id,
                "sucursale_id" => $user->sucursale_id,
                "is_admin" => $user->role_id == 1,
            ]
        ]);
    }

    /**
     * Obtener estadísticas recursivas de una sucursal
     */
    private function getSucursalStats($sucursaleId)
    {
        $allDocuments = Documentos::where('sucursale_id', $sucursaleId)->get();
        
        $totalFiles = $allDocuments->where('type', 'file')->count();
        $totalFolders = $allDocuments->where('type', 'folder')->count();
        $totalDocs = $allDocuments->count();

        return [
            'totalDocs' => $totalDocs,
            'totalFolders' => $totalFolders,
            'totalFiles' => $totalFiles,
        ];
    }

    // ========== MÉTODOS PRIVADOS ==========

    /**
     * Eliminar carpeta recursivamente
     */
    private function deleteFolderRecursive(Documentos $folder)
    {
        foreach ($folder->children as $child) {
            if ($child->isFolder()) {
                $this->deleteFolderRecursive($child);
            } else {
                if ($child->file_path && Storage::disk('public')->exists($child->file_path)) {
                    Storage::disk('public')->delete($child->file_path);
                }
                $child->delete();
            }
        }
    }

    /**
     * Generar nombre de archivo único
     */
    private function generateUniqueFileName($baseName, $extension, $sucursale_id, $parent_id)
    {
        $fileName = $baseName . '.' . $extension;
        $counter = 1;

        while ($this->fileNameExists($fileName, $sucursale_id, $parent_id)) {
            $fileName = $baseName . '(' . $counter . ').' . $extension;
            $counter++;
        }

        return $fileName;
    }

    /**
     * Verificar si existe un nombre de archivo
     */
    private function fileNameExists($fileName, $sucursale_id, $parent_id)
    {
        return Documentos::where('name', $fileName)
            ->where('sucursale_id', $sucursale_id)
            ->where('type', 'file')
            ->where(function($q) use ($parent_id) {
                if ($parent_id) {
                    $q->where('parent_id', $parent_id);
                } else {
                    $q->whereNull('parent_id');
                }
            })
            ->exists();
    }

    /**
     * Formatear documento para respuesta JSON
     */
    private function formatDocumento($documento, $user)
    {
        $isNew = false;
        
        // Verificar si es nuevo (menos de 7 días y no visto por el usuario)
        if ($documento->isFile()) {
            $daysSinceCreation = $documento->created_at->diffInDays(now());
            $hasBeenViewed = DocumentoView::where('documento_id', $documento->id)
                                         ->where('user_id', $user->id)
                                         ->exists();
            
            $isNew = ($daysSinceCreation <= 7) && !$hasBeenViewed;
        }

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
                ? env("APP_URL") . "/api/documentos/" . $documento->id . "/serve"
                : null,
            "mime_type" => $documento->mime_type,
            "size" => $documento->size,
            "size_formatted" => $documento->size ? $this->formatBytes($documento->size) : null,
            "description" => $documento->description,
            "children_count" => $documento->isFolder() ? $documento->children->count() : 0,
            "files_count" => $documento->isFolder() ? $documento->countAllFiles() : 0,
            "is_new" => $isNew,
            "created_at" => $documento->created_at->format("Y-m-d h:i A"),
            "updated_at" => $documento->updated_at->format("Y-m-d h:i A"),
        ];
    }

    /**
     * Formatear documento con hijos para árbol
     */
    private function formatDocumentoTree($documento, $user)
    {
        $formatted = $this->formatDocumento($documento, $user);
        
        if ($documento->isFolder() && $documento->relationLoaded('allChildren')) {
            $formatted['children'] = $documento->allChildren->map(function($child) use ($user) {
                return $this->formatDocumentoTree($child, $user);
            });
        }
        
        return $formatted;
    }

    /**
     * Formatear carpeta para árbol de selección
     */
    private function formatFolderForTree($folder)
    {
        $formatted = [
            "id" => $folder->id,
            "name" => $folder->name,
            "parent_id" => $folder->parent_id,
        ];
        
        if ($folder->relationLoaded('allChildren') && $folder->allChildren->count() > 0) {
            $formatted['children'] = $folder->allChildren->map(function($child) {
                return $this->formatFolderForTree($child);
            });
        }
        
        return $formatted;
    }

    /**
     * Formatear bytes
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
        
        // Descargar con el nombre original del documento
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
            "url" => env("APP_URL") . "/api/documentos/" . $documento->id . "/serve",
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

    /**
     * Servir archivo directamente (streaming seguro con autenticación).
     * Soluciona problemas de CORS y symlinks en producción.
     */
    public function serve($id)
    {
        $documento = Documentos::findOrFail($id);

        if ($documento->isFolder()) {
            return response()->json([
                'message' => 403,
                'message_text' => 'No aplica para carpetas'
            ], 403);
        }

        if (!$documento->file_path) {
            return response()->json([
                'message' => 404,
                'message_text' => 'Ruta de archivo no registrada'
            ], 404);
        }

        // En hosting compartido public/storage es carpeta real (no symlink)
        // por eso buscamos primero ahí, luego en storage/app/public como fallback
        $paths = [
            public_path('storage/' . $documento->file_path),
            storage_path('app/public/' . $documento->file_path),
        ];

        $filePath = null;
        foreach ($paths as $candidate) {
            if (file_exists($candidate)) {
                $filePath = $candidate;
                break;
            }
        }

        if (!$filePath) {
            Log::warning('Archivo no encontrado en disco', [
                'documento_id' => $id,
                'file_path'    => $documento->file_path,
                'paths_tried'  => $paths,
            ]);
            return response()->json([
                'message' => 404,
                'message_text' => 'Archivo físico no encontrado en disco'
            ], 404);
        }

        $mimeType = $documento->mime_type ?: mime_content_type($filePath);

        return response()->file($filePath, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $documento->name . '"',
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }
}