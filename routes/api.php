<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserAccessController;
use App\Http\Controllers\tasks\GruposController;
use App\Http\Controllers\tasks\ListasController;
use App\Http\Controllers\tasks\TareasController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\tasks\EtiquetasController;
use App\Http\Controllers\tasks\ChecklistsController;
use App\Http\Controllers\tasks\ComentariosController;
use App\Http\Controllers\documents\DocumentosController;
use App\Http\Controllers\Configuration\SucusaleController;
use App\Http\Controllers\Configuration\WarehouseController;
use App\Http\Controllers\Configuration\ClientSegmentController;
use App\Http\Controllers\Configuration\MethodPaymentController;
use App\Http\Controllers\Configuration\SucursaleDeliverieController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
 
    // 'middleware' => 'auth:api',
    'prefix' => 'auth',
//    'middleware' => ['auth:api'],//,'permission:publish articles|edit articles'
], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->name('me');
});

Route::group([
    'middleware' => 'auth:api',
], function ($router) {
    Route::resource("roles",RolePermissionController::class); 

    Route::get('/users/search', [GruposController::class, 'searchUsers']);      

    Route::post('/users/{id}', [UserAccessController::class, 'update']);
    Route::get("users/config", [UserAccessController::class, 'config']);
    Route::resource("users",UserAccessController::class); 

    // Route::resource('task');

    Route::resource("sucursales",SucusaleController::class); 
    Route::resource("warehouses",WarehouseController::class); 
    Route::resource("sucursale_deliveries",SucursaleDeliverieController::class); 
    Route::resource("method_payments",MethodPaymentController::class); 
    Route::resource("client_segments",ClientSegmentController::class); 

    // Route::post('/tareas/{id}', [TareasController::class, 'update']);
    Route::get("tareas/config",[TareasController::class, 'config']);
    Route::post('/tareas/{id}/move', [TareasController::class, 'move']);
    Route::resource("tareas",TareasController::class);

    Route::get('/tareas/{tareaId}/timeline', [ComentariosController::class, 'index']);
    Route::post('/tareas/{tareaId}/comentarios', [ComentariosController::class, 'store']);
    Route::put('/tareas/{tareaId}/comentarios/{comentarioId}', [ComentariosController::class, 'update']);
    Route::delete('/tareas/{tareaId}/comentarios/{comentarioId}', [ComentariosController::class, 'destroy']);

    Route::post('/grupos/{id}/toggle-star', [GruposController::class, 'toggleStar']);
    Route::post('/grupos/{id}/share', [GruposController::class, 'share']);
    Route::delete('/grupos/{grupoId}/unshare/{userId}', [GruposController::class, 'unshare']);
    // Agregar esta ruta en api.php dentro del grupo con middleware auth:api
    Route::get('/grupos/{id}/shared-users', [GruposController::class, 'getSharedUsers']);
    Route::resource("grupos", GruposController::class);

    Route::get("documentos/config",[DocumentosController::class, 'config']);
    Route::resource("documentos",DocumentosController::class);

    Route::post('listas/reorder', [ListasController::class, 'reorder']);
    Route::apiResource('listas',ListasController::class);
    
    // Etiquetas
    Route::get('/tareas/{tareaId}/etiquetas', [EtiquetasController::class, 'index']);
    Route::post('/tareas/{tareaId}/etiquetas', [EtiquetasController::class, 'store']);
    Route::put('/tareas/{tareaId}/etiquetas/{etiquetaId}', [EtiquetasController::class, 'update']);
    Route::delete('/tareas/{tareaId}/etiquetas/{etiquetaId}', [EtiquetasController::class, 'destroy']);

    // Checklists
    Route::get('/tareas/{tareaId}/checklists', [ChecklistsController::class, 'index']);
    Route::post('/tareas/{tareaId}/checklists', [ChecklistsController::class, 'store']);
    Route::put('/tareas/{tareaId}/checklists/{checklistId}', [ChecklistsController::class, 'update']);
    Route::delete('/tareas/{tareaId}/checklists/{checklistId}', [ChecklistsController::class, 'destroy']);

    // Checklist Items
    Route::post('/tareas/{tareaId}/checklists/{checklistId}/items', [ChecklistsController::class, 'addItem']);
    Route::put('/tareas/{tareaId}/checklists/{checklistId}/items/{itemId}', [ChecklistsController::class, 'updateItem']);
    Route::delete('/tareas/{tareaId}/checklists/{checklistId}/items/{itemId}', [ChecklistsController::class, 'destroyItem']);


});