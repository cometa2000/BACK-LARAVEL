<?php

namespace App\Http\Controllers\tasks;

use App\Models\User;
use App\Models\tasks\Tareas;
use Illuminate\Http\Request;

use App\Models\tasks\Actividad;
use App\Http\Controllers\Controller;
use App\Models\Configuration\Sucursale;
use Illuminate\Support\Facades\Log;

class TareasController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get("search");
        $grupo_id = $request->get("grupo_id");

        // $tareas = Tareas::where("name","like","%".$search."%")->orderBy("id","desc")->paginate(25);

        $query = Tareas::where("name", "like", "%" . $search . "%");
    
        // Filtrar por grupo si se proporciona
        if ($grupo_id) {
            $query->where('grupo_id', $grupo_id);
        }
        
        $tareas = $query->orderBy("id", "desc")->paginate(25);

        return response()->json([
            "total" => $tareas->total(),
            "tareas" => $tareas->map(function($tarea) {
                return [
                    "name" => $tarea->name,
                    "description" => $tarea->description,
                    "type_task" => $tarea->type_task,     // Diferencia de tarea
                    "priority" => $tarea->priority,
                    "start_date" => $tarea->start_date,
                    "due_date" => $tarea->due_date,
                    "status" => $tarea->status,
                    
                    "sucursale_id" => $tarea->sucursale_id,   // A qué sucursal va dirigida
                    "sucursale" => $tarea->sucursale,
                    "sucursales" => $tarea->sucursales,

                    "user_id" => $tarea->user_id,        // Responsable directo
                    "user" => $tarea->user,

                    "grupo_id" => $tarea->grupo_id,
                    "grupo" => $tarea->grupo,

                    "lista_id"=> $tarea->lista_id,
                    "lista"=> $tarea->lista,

                    // Campos que se usan SOLO en tareas simples
                    "estimated_time" => $tarea->estimated_time,  // Ej: "3 días", "2 horas"
                    "file_path" => $tarea->file_path ? env("APP_URL")."/storage/".$tarea->file_path : NULL,       // Archivo adjunto

                    // Campos que se usan SOLO en eventos
                    "budget" => $tarea->budget,           // Presupuesto
                    "address" => $tarea->address,          // Lugar
                    "attendees" => $tarea->attendees,        // Número de asistentes
                    "subtasks" => $tarea->subtasks,         // Guardar lista de subtareas como JSON

                    "created_at" => $tarea->created_at->format("Y-m-d h:i A")
                ];
            }),
        ]);
    }

    public function config(){
        return response()->json([
            "user" => User::all(), 
            "sucursales" => Sucursale::all(),
            // "sucursales" => Sucursale::where("state",1)->get(),
        ]);
    }

    /**
     * Store a newly created resource in /storage.
     */
    public function store(Request $request)
    {
        $is_exits_tarea = Tareas::where("name", $request->name)->first();
        if ($is_exits_tarea) {
            return response()->json([
                "message" => 403,
                "message_text" => "El nombre de la tarea ya existe"
            ]);
        }

        // ✅ 1. Fusiona el user_id autenticado en la solicitud
        $request->merge([
            'user_id' => auth()->id()
        ]);

        // ✅ 2. Crea la tarea con el user_id ya incluido
        $tarea = Tareas::create($request->all());

        return response()->json([
            "message" => 200,
            "tarea" => [
                "id" => $tarea->id,
                "user_id" => $tarea->user_id,
                "user" => $tarea->user,
                "grupo_id" => $tarea->grupo_id,
                "grupo" => $tarea->grupo,
                "lista_id" => $tarea->lista_id,
                "lista" => $tarea->lista,
                "name" => $tarea->name,
                "description" => $tarea->description,
                "type_task" => $tarea->type_task,
                "priority" => $tarea->priority,
                "start_date" => $tarea->start_date,
                "due_date" => $tarea->due_date,
                "status" => $tarea->status,
                "estimated_time" => $tarea->estimated_time,
                "file_path" => $tarea->file_path
                    ? env("APP_URL") . "/storage/" . $tarea->file_path
                    : null,
                "budget" => $tarea->budget,
                "address" => $tarea->address,
                "attendees" => $tarea->attendees,
                "subtasks" => $tarea->subtasks,
                "created_at" => $tarea->created_at->format("Y-m-d h:i A")
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
     * Update the specified resource in /storage.
     */
    public function update(Request $request, string $id)
    {
        $is_exits_tarea = Tareas::where("name",$request->name)
                            ->where("id","<>",$id)->first();
        if($is_exits_tarea){
            return response()->json([
                "message" => 403,
                "message_text" => "El nombre de la tarea ya existe"
            ]);
        }
        
        $tarea = Tareas::findOrFail($id);
        $cambios = [];
        $descripcionesActividades = [];

        // Detectar cambios específicos
        if ($request->has('priority') && $tarea->priority != $request->priority) {
            $cambios['priority'] = [
                'old' => $tarea->priority,
                'new' => $request->priority
            ];
            $prioridades = [
                'low' => 'Baja',
                'medium' => 'Media',
                'high' => 'Alta'
            ];
            $descripcionesActividades[] = "cambió la prioridad de {$prioridades[$tarea->priority]} a {$prioridades[$request->priority]}";
        }

        if ($request->has('due_date') && $tarea->due_date != $request->due_date) {
            $cambios['due_date'] = [
                'old' => $tarea->due_date,
                'new' => $request->due_date
            ];
            if ($request->due_date) {
                $descripcionesActividades[] = "añadió la fecha de vencimiento: " . date('d/m/Y', strtotime($request->due_date));
            }
        }

        if ($request->has('start_date') && $tarea->start_date != $request->start_date) {
            $cambios['start_date'] = [
                'old' => $tarea->start_date,
                'new' => $request->start_date
            ];
            if ($request->start_date) {
                $descripcionesActividades[] = "añadió la fecha de inicio: " . date('d/m/Y', strtotime($request->start_date));
            }
        }

        if ($request->has('user_id') && $tarea->user_id != $request->user_id) {
            $cambios['user_id'] = [
                'old' => $tarea->user_id,
                'new' => $request->user_id
            ];
            $usuario = \App\Models\User::find($request->user_id);
            if ($usuario) {
                $descripcionesActividades[] = "asignó la tarea a {$usuario->name}";
            }
        }

        if ($request->has('description') && $tarea->description != $request->description) {
            $cambios['description'] = true;
            $descripcionesActividades[] = "actualizó la descripción";
        }

        if ($request->has('name') && $tarea->name != $request->name) {
            $cambios['name'] = [
                'old' => $tarea->name,
                'new' => $request->name
            ];
            $descripcionesActividades[] = "cambió el nombre de la tarea";
        }

        // Actualizar la tarea
        $tarea->update($request->all());

        // Registrar actividades solo si hubo cambios
        if (!empty($descripcionesActividades)) {
            foreach ($descripcionesActividades as $descripcion) {
                Actividad::create([
                    'type' => 'updated',
                    'description' => $descripcion,
                    'changes' => $cambios,
                    'tarea_id' => $tarea->id,
                    'user_id' => auth()->id()
                ]);
            }
        }

        return response()->json([
            "message" => 200,
            "tarea" => [
                "id" => $tarea->id,
                "sucursale_id" => $tarea->sucursale_id,
                "sucursale" => $tarea->sucursale,
                "user_id" => $tarea->user_id,
                "user" => $tarea->user,
                "grupo_id" => $tarea->grupo_id,
                "grupo" => $tarea->grupo,
                "lista_id" => $tarea->lista_id,
                "lista" => $tarea->lista,
                "name" => $tarea->name,
                "description" => $tarea->description,
                "type_task" => $tarea->type_task,
                "priority" => $tarea->priority,
                "start_date" => $tarea->start_date,
                "due_date" => $tarea->due_date,
                "status" => $tarea->status,
                "estimated_time" => $tarea->estimated_time,
                "file_path" => $tarea->file_path ? env("APP_URL")."/storage/".$tarea->file_path : NULL,
                "budget" => $tarea->budget,
                "address" => $tarea->address,
                "attendees" => $tarea->attendees,
                "subtasks" => $tarea->subtasks,
                "created_at" => $tarea->created_at->format("Y-m-d h:i A")
            ],
        ]);
    }

    /**
     * Remove the specified resource from /storage.
     */
    public function destroy(string $id)
    {
        $tarea = Tareas::findOrFail($id);
        // VALIDACION POR PROFORMA
        $tarea->delete();
        return response()->json([
            "message" => 200,
        ]);
    }


    

    public function move(Request $request, $id)
    {
        // ✅ Validar que lista_id venga en el request
        $request->validate([
            'lista_id' => 'required|exists:listas,id'
        ]);

        try {
            $tarea = Tareas::findOrFail($id);
            
            // ✅ Obtener la lista destino
            $lista = \App\Models\tasks\Lista::findOrFail($request->input('lista_id'));
            
            // ✅ (Opcional) Verificar que ambas pertenezcan al mismo grupo
            // if ($tarea->grupo_id != $lista->grupo_id) {
            //     return response()->json([
            //         'message' => 403,
            //         'error' => 'La lista no pertenece al mismo grupo'
            //     ], 403);
            // }

            // ✅ Actualizar la tarea
            $tarea->update([
                'lista_id' => $request->input('lista_id'),
            ]);

            // ✅ Registrar actividad
            \App\Models\tasks\Actividad::create([
                'type' => 'moved',
                'description' => "movió la tarea a la lista '{$lista->name}'",
                'changes' => [
                    'lista_id' => [
                        'old' => $tarea->lista_id,
                        'new' => $lista->id
                    ]
                ],
                'tarea_id' => $tarea->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 200,
                'tarea' => $tarea->fresh(['lista', 'user', 'grupo'])
            ]);

        } catch (\Exception $e) {
            Log::error('Error al mover tarea: ' . $e->getMessage());
            return response()->json([
                'message' => 500,
                'error' => 'Error al mover la tarea: ' . $e->getMessage()
            ], 500);
        }
    }
}
