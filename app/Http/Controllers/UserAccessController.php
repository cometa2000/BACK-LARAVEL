<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\Configuration\Sucursale;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserAccessController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize("viewAny",User::class);
        $search = $request->get("search");

        $users = User::where("name","like","%".$search."%")->orderBy("id","desc")->paginate(25);

        return response()->json([
            "total" => $users->total(),
            "users" => $users->map(function($user) {
                return [
                    "id" => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    "surname" => $user->surname,
                    "full_name" => $user->name.' '.$user->surname,
                    "phone" =>  $user->phone,
                    "role_id" => $user->role_id,
                    "role" => $user->role,
                    "roles" => $user->roles,
                    "sucursale_id" => $user->sucursale_id,
                    "sucursal" => $user->sucursale,
                    "type_document" => $user->type_document,
                    "n_document" => $user->n_document,
                    "gender" => $user->gender,
                    // ✅ Devolver solo el nombre del archivo (ej: "1.png")
                    "avatar" => $user->avatar ?? '1.png',
                    "created_format_at" => $user->created_at->format("Y-m-d h:i A"),
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
     * Genera una contraseña temporal basada en los datos del usuario
     * Formato: [2 chars sucursal][1 char nombre][2 chars apellido][3 chars teléfono][3 chars aleatorios]
     * Ejemplo: cibso551j34
     * 
     * @param string $sucursalName - Nombre de la sucursal
     * @param string $name - Nombre del usuario
     * @param string $surname - Apellido del usuario
     * @param string $phone - Teléfono del usuario
     * @return string - Contraseña generada
     */
    private function generateTemporaryPassword($sucursalName, $name, $surname, $phone)
    {
        // Limpiar y normalizar las cadenas (quitar acentos, espacios, etc.)
        $sucursalClean = $this->cleanString($sucursalName);
        $nameClean = $this->cleanString($name);
        $surnameClean = $this->cleanString($surname);
        $phoneClean = preg_replace('/[^0-9]/', '', $phone); // Solo números
        
        // Extraer las partes según el formato especificado
        $sucursalPart = strtolower(substr($sucursalClean, 0, 2)); // 2 primeros caracteres de sucursal
        $namePart = strtolower(substr($nameClean, 0, 1)); // 1er carácter del nombre
        $surnamePart = strtolower(substr($surnameClean, 0, 2)); // 2 primeros caracteres del apellido
        $phonePart = substr($phoneClean, 0, 3); // 3 primeros dígitos del teléfono
        
        // Generar 3 caracteres aleatorios (letras minúsculas y números)
        $randomPart = $this->generateRandomChars(3);
        
        // Construir la contraseña
        $password = $sucursalPart . $namePart . $surnamePart . $phonePart . $randomPart;
        
        // Asegurar que tenga al menos 8 caracteres (por seguridad)
        if (strlen($password) < 8) {
            $password .= $this->generateRandomChars(8 - strlen($password));
        }
        
        return $password;
    }
    
    /**
     * Limpia una cadena removiendo acentos, espacios y caracteres especiales
     * 
     * @param string $string
     * @return string
     */
    private function cleanString($string)
    {
        // Convertir acentos a caracteres normales
        $unwanted_array = [
            'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u',
            'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U',
            'ñ'=>'n', 'Ñ'=>'N'
        ];
        $string = strtr($string, $unwanted_array);
        
        // Remover espacios y caracteres especiales, dejar solo letras
        $string = preg_replace('/[^a-zA-Z]/', '', $string);
        
        return $string;
    }
    
    /**
     * Genera caracteres aleatorios (letras minúsculas y números)
     * 
     * @param int $length - Longitud de la cadena a generar
     * @return string
     */
    private function generateRandomChars($length)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // $this->authorize("create",User::class);
        $USER_EXITS = User::where("email",$request->email)->first();
        if($USER_EXITS){
            return response()->json([
                "message" => 403,
                "message_text" => "EL USUARIO YA EXISTE"
            ]);
        }

        // ✅ ACTUALIZADO: Ya no procesamos archivo de imagen, solo recibimos el nombre del avatar
        // Si se recibe un avatar, simplemente lo guardamos como está (ejemplo: "3.png")
        if ($request->has("avatar")) {
            $request->request->add(["avatar" => $request->avatar]);
        } else {
            // Avatar por defecto si no se proporciona
            $request->request->add(["avatar" => "1.png"]);
        }

        // ✅ GENERAR CONTRASEÑA TEMPORAL AUTOMÁTICAMENTE
        // Obtener el nombre de la sucursal
        $sucursal = Sucursale::find($request->sucursale_id);
        $sucursalName = $sucursal ? $sucursal->name : 'default';
        
        // Generar la contraseña
        $generatedPassword = $this->generateTemporaryPassword(
            $sucursalName,
            $request->name,
            $request->surname,
            $request->phone
        );
        
        // Encriptar y agregar la contraseña generada
        $request->request->add(["password" => bcrypt($generatedPassword)]);

        $role = Role::findOrFail($request->role_id);
        $user = User::create($request->all());
        $user->assignRole($role);
        
        // Recargar el usuario con sus relaciones
        $user->load('role', 'roles', 'sucursale');
        
        return response()->json([
            "message" => 200,
            "generated_password" => $generatedPassword, // ✅ Devolver la contraseña generada (SIN ENCRIPTAR) al frontend
            "user" => [
                "id" => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                "surname" => $user->surname,
                "full_name" => $user->name.' '.$user->surname,
                "phone" =>  $user->phone,
                "role_id" => $user->role_id,
                "role" => $user->role,
                "roles" => $user->roles,
                "sucursale_id" => $user->sucursale_id,
                "sucursal" => $user->sucursale,
                "type_document" => $user->type_document,
                "n_document" => $user->n_document,
                "gender" => $user->gender,
                // ✅ Devolver solo el nombre del archivo (ej: "1.png")
                "avatar" => $user->avatar ?? '1.png',
                "created_format_at" => $user->created_at->format("Y-m-d h:i A"),
            ]
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
        // $this->authorize("update",User::class);
        $USER_EXITS = User::where("email",$request->email)
                        ->where("id","<>",$id)->first();
        if($USER_EXITS){
            return response()->json([
                "message" => 403,
                "message_text" => "EL USUARIO YA EXISTE"
            ]);
        }

        $user = User::findOrFail($id);

        // ✅ ACTUALIZADO: Ya no procesamos archivo de imagen, solo recibimos el nombre del avatar
        // Si se recibe un avatar, simplemente lo guardamos como está (ejemplo: "3.png")
        if ($request->has("avatar")) {
            // Si el usuario tenía un avatar antiguo en storage, lo eliminamos
            if ($user->avatar && strpos($user->avatar, 'storage') !== false) {
                Storage::delete($user->avatar);
            }
            $request->request->add(["avatar" => $request->avatar]);
        }

        // ⚠️ IMPORTANTE: En la actualización, solo encriptar si se proporciona una nueva contraseña
        if($request->password){
            $request->request->add(["password" => bcrypt($request->password)]);
        }

        if($request->role_id != $user->role_id){
            // EL VIEJO ROL
            $role_old = Role::findOrFail($user->role_id);
            $user->removeRole($role_old);

            // EL NUEVO ROL
            $role = Role::findOrFail($request->role_id);
            $user->assignRole($role);
        }

        $user->update($request->all());
        
        // Recargar el usuario con sus relaciones
        $user->load('role', 'roles', 'sucursale');
        
        return response()->json([
            "message" => 200,
            "user" => [
                "id" => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                "surname" => $user->surname,
                "full_name" => $user->name.' '.$user->surname,
                "phone" =>  $user->phone,
                "role_id" => $user->role_id,
                "role" => $user->role,
                "roles" => $user->roles,
                "sucursale_id" => $user->sucursale_id,
                "sucursal" => $user->sucursale,
                "type_document" => $user->type_document,
                "n_document" => $user->n_document,
                "gender" => $user->gender,
                // ✅ Devolver solo el nombre del archivo (ej: "1.png")
                "avatar" => $user->avatar ?? '1.png',
                "created_format_at" => $user->created_at->format("Y-m-d h:i A"),
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // $this->authorize("delete",User::class);
        $user = User::findOrFail($id);
        
        // ✅ ACTUALIZADO: Solo eliminar si es un archivo antiguo en storage
        if($user->avatar && strpos($user->avatar, 'storage') !== false){
            Storage::delete($user->avatar);
        }
        
        $user->delete();
        return response()->json([
            "message" => 200,
        ]);
    }
}