<?php

namespace App\Http\Controllers\documents;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Models\documents\documentos;
use App\Models\Configuration\Sucursale;
use Illuminate\Support\Facades\Storage;


class DocumentosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get("search");

        $documentos = documentos::where("name","like","%".$search."%")->orderBy("id","desc")->paginate(15);

        return response()->json([
            "total" => $documentos->total(),
            "documentos" => $documentos->map(function($documento) {
                return [
                    "id" => $documento->id,
                    "name" => $documento->name,
                    "type" => $documento->type,

                    "parent_id" => $documento->parent_id,

                    "sucursale_id" => $documento->sucursale_id,
                    "sucursale" => $documento->sucursale,
                    "sucursales" => $documento->sucursales,

                    "user_id" => $documento->user_id,
                    "user" => $documento->user,

                    "file_path" => $documento->file_path 
                        ? env("APP_URL")."/storage/".$documento->file_path 
                        : null,

                    "mime_type" => $documento->mime_type,
                    "size" => $documento->size,
                    "description" => $documento->description,
                    
                    "created_at" => $documento->created_at->format("Y-m-d h:i A")
                ];
            }),
        ]);
    }

    public function config(){
        return response()->json([
            "roles" => Role::all(), 
            "sucursales" => Sucursale::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $is_exits_documento = documentos::where("name", $request->name)->first();
        if ($is_exits_documento) {
            return response()->json([
                "message" => 403,
                "message_text" => "El nombre del documento ya existe"
            ]);
        }

        $file_path = null;
        $mime_type = null;
        $size = null;

        if ($request->hasFile("file")) {
            $file = $request->file("file");
            $file_path = $file->store("documentos", "public"); // guarda en storage/app/public/documentos
            $mime_type = $file->getClientMimeType();
            $size = $file->getSize();
        }

        $documento = documentos::create([
            "name" => $request->name,
            "type" => "file",
            "parent_id" => $request->parent_id,
            "sucursale_id" => $request->sucursale_id,
            "user_id" => auth()->id(), // opcional
            "file_path" => $file_path,
            "mime_type" => $mime_type,
            "size" => $size,
            "description" => $request->description,
        ]);

        return response()->json([
            "message" => 200,
            "documento" => [
                "id" => $documento->id,
                "name" => $documento->name,
                "type" => $documento->type,
                "sucursale_id" => $documento->sucursale_id,
                "sucursale" => $documento->sucursale,
                "user_id" => $documento->user_id,
                "user" => $documento->user,
                "file_path" => $documento->file_path 
                    ? env("APP_URL")."/storage/".$documento->file_path 
                    : null,
                "mime_type" => $documento->mime_type,
                "size" => $documento->size,
                "description" => $documento->description,
                "created_at" => $documento->created_at->format("Y-m-d h:i A")
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // $is_exits_documento = documentos::where("name",$request->name)
        //                     ->where("id","<>",$id)->first();
        // if($is_exits_documento){
        //     return response()->json([
        //         "message" => 403,
        //         "message_text" => "El nombre del documento ya existe"
        //     ]);
        // }
        $documento = documentos::findOrFail($id);
        $documento->update($request->all());
        return response()->json([
            "message" => 200,
            "documentos" => [
                "id" => $documento->id,
                "name" => $documento->name,
                "type" => $documento->type,

                "parent_id" => $documento->parent_id,

                "sucursale_id" => $documento->sucursale_id,
                "sucursale" => $documento->sucursale,
                "sucursales" => $documento->sucursales,

                "surname" => $documento->surname,
                "user_id" => $documento->user_id,
                "user" => $documento->user,

                "file_path" => $documento->file_path 
                    ? env("APP_URL")."/storage/".$documento->file_path 
                    : null,

                "mime_type" => $documento->mime_type,
                "size" => $documento->size,
                "description" => $documento->description,
                
                "created_at" => $documento->created_at->format("Y-m-d h:i A")
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $documento = documentos::findOrFail($id);
        // VALIDACION POR PROFORMA
        $documento->delete();
        return response()->json([
            "message" => 200,
        ]);
    }
}
