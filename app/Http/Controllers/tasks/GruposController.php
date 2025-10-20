<?php

namespace App\Http\Controllers\tasks;

use App\Models\User;
use App\Models\tasks\Grupos;
use Illuminate\Http\Request;
use App\Mail\GrupoCreadoMail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class GruposController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get("search");
        $userId = auth()->id();

        // Obtener grupos propios y compartidos
        $grupos = Grupos::accessibleBy($userId)
            ->where('name', 'like', "%{$search}%")
            ->orderBy('is_starred', 'desc') // ⭐ Primero los marcados
            ->orderBy('id', 'desc')
            ->paginate(25);

        return response()->json([
            "total" => $grupos->total(),
            "grupos" => $grupos->map(function($grupo) use ($userId) {
                return [
                    "id" => $grupo->id,
                    "name" => $grupo->name,
                    "color" => $grupo->color,
                    "image" => $grupo->image ? env("APP_URL")."/storage/".$grupo->image : NULL,
                    "user_id" => $grupo->user_id,
                    "user" => $grupo->user,
                    "is_starred" => $grupo->is_starred,
                    "is_owner" => $grupo->user_id == $userId,
                    "shared_with" => $grupo->sharedUsers->pluck('name')->toArray(),
                    "created_at" => $grupo->created_at->format("Y-m-d h:i A")
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['user_id' => auth()->id()]);
        $grupo = Grupos::create($request->all());

        // Enviar correo
        Mail::to(auth()->user()->email)
            ->send(new GrupoCreadoMail($grupo->name, auth()->user()->name));

        return response()->json([
            "message" => 200,
            "grupo" => [
                "id" => $grupo->id,
                "name" => $grupo->name,
                "color" => $grupo->color,
                "image" => $grupo->image ? env("APP_URL")."/storage/".$grupo->image : NULL,
                "user_id" => $grupo->user_id,
                "is_starred" => $grupo->is_starred ?? false,
                "is_owner" => true,
                "created_at" => $grupo->created_at->format("Y-m-d h:i A")
            ],
        ]);
    }

    public function update(Request $request, string $id)
    {
        $is_exits_grupo = Grupos::where("name",$request->name)
                            ->where("id","<>",$id)->first();
        if($is_exits_grupo){
            return response()->json([
                "message" => 403,
                "message_text" => "El nombre del grupo ya existe"
            ]);
        }
        
        $grupo = Grupos::findOrFail($id);
        $grupo->update($request->all());
        
        return response()->json([
            "message" => 200,
            "grupo" => [
                "id" => $grupo->id,
                "name" => $grupo->name,
                "color" => $grupo->color,
                "image" => $grupo->image ? env("APP_URL")."/storage/".$grupo->image : NULL,
                "user_id" => $grupo->user_id,
                "is_starred" => $grupo->is_starred,
                "created_at" => $grupo->created_at->format("Y-m-d h:i A")
            ],
        ]);
    }

    public function destroy(string $id)
    {
        $grupo = Grupos::findOrFail($id);
        
        // Verificar que el usuario sea el propietario
        if ($grupo->user_id != auth()->id()) {
            return response()->json([
                "message" => 403,
                "message_text" => "No tienes permiso para eliminar este grupo"
            ], 403);
        }
        
        $grupo->delete();
        return response()->json(["message" => 200]);
    }

    // ⭐ Marcar/Desmarcar grupo
    public function toggleStar($id)
    {
        $grupo = Grupos::findOrFail($id);
        
        // Solo el propietario o usuarios compartidos pueden marcar
        if ($grupo->user_id != auth()->id() && !$grupo->sharedUsers->contains(auth()->id())) {
            return response()->json([
                "message" => 403,
                "message_text" => "No tienes acceso a este grupo"
            ], 403);
        }

        $grupo->is_starred = !$grupo->is_starred;
        $grupo->save();

        return response()->json([
            "message" => 200,
            "is_starred" => $grupo->is_starred
        ]);
    }

    // 📤 Compartir grupo con usuarios
    public function share(Request $request, $id)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $grupo = Grupos::findOrFail($id);
        
        // Verificar que el usuario sea el propietario
        if ($grupo->user_id != auth()->id()) {
            return response()->json([
                "message" => 403,
                "message_text" => "Solo el propietario puede compartir este grupo"
            ], 403);
        }

        // Sincronizar usuarios (añade nuevos, mantiene existentes)
        $grupo->sharedUsers()->syncWithoutDetaching($request->user_ids);

        return response()->json([
            "message" => 200,
            "shared_with" => $grupo->sharedUsers->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ];
            })
        ]);
    }

    // 🔍 Buscar usuarios para compartir
    public function searchUsers(Request $request)
    {
        $search = $request->get('search', '');
        
        // Si no hay búsqueda o es muy corta, no devolver nada
        if (empty(trim($search)) || strlen(trim($search)) < 2) {
            return response()->json([
                'message' => 200,
                'users' => []
            ]);
        }
        
        $users = User::where('id', '!=', auth()->id())
            ->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('surname', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            })
            ->select('id', 'name', 'surname', 'email', 'phone', 'avatar')
            ->orderBy('name', 'asc')
            ->limit(10)
            ->get();

        return response()->json([
            'message' => 200,
            'users' => $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => trim($user->name . ' ' . ($user->surname ?? '')),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar 
                        ? env("APP_URL")."/storage/".$user->avatar 
                        : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'
                ];
            })
        ]);
    }
    

    // ❌ Dejar de compartir con un usuario específico
    public function unshare($grupoId, $userId)
    {
        $grupo = Grupos::findOrFail($grupoId);
        
        if ($grupo->user_id != auth()->id()) {
            return response()->json([
                "message" => 403,
                "message_text" => "Solo el propietario puede dejar de compartir"
            ], 403);
        }

        $grupo->sharedUsers()->detach($userId);

        return response()->json(["message" => 200]);
    }

    // Agregar este método en GruposController.php

    /**
     * Obtener usuarios con los que ya está compartido el grupo
     */
    public function getSharedUsers($id)
    {
        $grupo = Grupos::findOrFail($id);
        
        // Verificar acceso
        if ($grupo->user_id != auth()->id() && !$grupo->sharedUsers->contains(auth()->id())) {
            return response()->json([
                "message" => 403,
                "message_text" => "No tienes acceso a este grupo"
            ], 403);
        }

        return response()->json([
            "message" => 200,
            "shared_users" => $grupo->sharedUsers->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => trim($user->name . ' ' . ($user->surname ?? '')),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar 
                        ? env("APP_URL")."/storage/".$user->avatar 
                        : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'
                ];
            })
        ]);
    }
    
}