<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User with all relationships.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = auth('api')->user();
        
        // Cargar las relaciones necesarias
        $user->load('role', 'roles', 'sucursale');
        
        // Obtener permisos
        $permissions = $user->getAllPermissions()->map(function($perm) {
            return $perm->name;
        });
        
        return response()->json([
            "id" => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            "surname" => $user->surname,
            "full_name" => $user->name.' '.$user->surname,
            "phone" => $user->phone,
            "role_id" => $user->role_id,
            "role" => $user->role,
            "role_name" => $user->role ? $user->role->name : null,
            "roles" => $user->roles,
            "sucursale_id" => $user->sucursale_id,
            "sucursal" => $user->sucursale,
            "sucursale_name" => $user->sucursale ? $user->sucursale->name : null,
            "type_document" => $user->type_document,
            "n_document" => $user->n_document,
            "gender" => $user->gender,
            // ✅ Devolver solo el nombre del archivo (ej: "1.png")
            "avatar" => $user->avatar ?? '1.png',
            "permissions" => $permissions,
            "created_format_at" => $user->created_at->format("Y-m-d h:i A"),
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $user = auth('api')->user();
        
        // Cargar las relaciones necesarias
        $user->load('role', 'roles', 'sucursale');
        
        // Obtener permisos
        $permissions = $user->getAllPermissions()->map(function($perm) {
            return $perm->name;
        });
        
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => [
                "id" => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                "surname" => $user->surname,
                "full_name" => $user->name.' '.$user->surname,
                "phone" => $user->phone,
                "role_id" => $user->role_id,
                "role" => $user->role,
                "role_name" => $user->role ? $user->role->name : null,
                "roles" => $user->roles,
                "sucursale_id" => $user->sucursale_id,
                "sucursal" => $user->sucursale,
                "sucursale_name" => $user->sucursale ? $user->sucursale->name : null,
                "type_document" => $user->type_document,
                "n_document" => $user->n_document,
                "gender" => $user->gender,
                // ✅ Devolver solo el nombre del archivo (ej: "1.png")
                "avatar" => $user->avatar ?? '1.png',
                "permissions" => $permissions,
                "created_format_at" => $user->created_at->format("Y-m-d h:i A"),
            ]
        ]);
    }
}