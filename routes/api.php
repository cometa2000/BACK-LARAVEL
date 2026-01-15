<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActividadController;
use App\Http\Controllers\UserAccessController;
use App\Http\Controllers\tasks\GanttController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\tasks\GruposController;
use App\Http\Controllers\tasks\ListasController;
use App\Http\Controllers\tasks\TareasController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\tasks\EtiquetasController;
use App\Http\Controllers\tasks\WorkspaceController;
use App\Http\Controllers\tasks\ChecklistsController;
use App\Http\Controllers\tasks\ComentariosController;
use App\Http\Controllers\tasks\TareaAdjuntosController;
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
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
    'prefix' => 'auth',
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
    
    // ========================================
    // 🏢 WORKSPACES (NUEVO)
    // ========================================
    Route::prefix('workspaces')->group(function () {
        Route::get('/', [WorkspaceController::class, 'index']); // Listar todos
        Route::post('/', [WorkspaceController::class, 'store']); // Crear
        Route::get('/{id}', [WorkspaceController::class, 'show']); // Ver uno
        Route::put('/{id}', [WorkspaceController::class, 'update']); // Actualizar
        Route::delete('/{id}', [WorkspaceController::class, 'destroy']); // Eliminar
        Route::get('/{id}/stats', [WorkspaceController::class, 'stats']); // Estadísticas
        Route::get('/{id}/grupos', [WorkspaceController::class, 'getWorkspaceGroups']); // Grupos del workspace
    });



    // Calendario de eventos
    Route::get('calendar-events', [CalendarEventController::class, 'index']);
    Route::post('calendar-events', [CalendarEventController::class, 'store']);
    Route::get('calendar-events/{id}', [CalendarEventController::class, 'show']);
    Route::put('calendar-events/{id}', [CalendarEventController::class, 'update']);
    Route::delete('calendar-events/{id}', [CalendarEventController::class, 'destroy']);
    
    // ========================================
    // ROLES Y USUARIOS
    // ========================================
    Route::resource("roles", RolePermissionController::class);
    Route::get('/users/search', [GruposController::class, 'searchUsers']);
    Route::post('/users/{id}', [UserAccessController::class, 'update']);
    Route::get("users/config", [UserAccessController::class, 'config']);
    Route::resource("users", UserAccessController::class);

    // ========================================
    // CONFIGURACIÓN
    // ========================================
    Route::resource("sucursales", SucusaleController::class);
    Route::resource("warehouses", WarehouseController::class);
    Route::resource("sucursale_deliveries", SucursaleDeliverieController::class);
    Route::resource("method_payments", MethodPaymentController::class);
    Route::resource("client_segments", ClientSegmentController::class);

    // ========================================
    // 📁 GRUPOS (ACTUALIZADO CON WORKSPACES)
    // ========================================
    // Funcionalidades básicas
    Route::resource("grupos", GruposController::class);
    
    // Funcionalidades adicionales
    Route::post('/grupos/{id}/toggle-star', [GruposController::class, 'toggleStar']);
    Route::post('/grupos/{id}/share', [GruposController::class, 'share']);
    Route::delete('/grupos/{grupoId}/unshare/{userId}', [GruposController::class, 'unshare']);
    Route::get('/grupos/{id}/shared-users', [GruposController::class, 'getSharedUsers']);
    
    // 🆕 NUEVO: Mover grupo a workspace
    Route::post('/grupos/{id}/move', [GruposController::class, 'moveToWorkspace']);
    
    // Permisos de grupos
    Route::get('/grupos/{id}/permissions', [GruposController::class, 'getPermissions']);
    Route::post('/grupos/{id}/permissions/type', [GruposController::class, 'updatePermissionType']);
    Route::post('/grupos/{grupoId}/permissions/user/{userId}', [GruposController::class, 'updateUserPermission']);
    Route::post('/grupos/{id}/permissions/batch', [GruposController::class, 'updateBatchPermissions']);
    Route::get('/grupos/{id}/check-write-access', [GruposController::class, 'checkWriteAccess']);

    // ========================================
    // 📋 LISTAS
    // ========================================
    Route::post('listas/reorder', [ListasController::class, 'reorder']);
    Route::apiResource('listas', ListasController::class);

    // ========================================
    // ✅ TAREAS
    // ========================================
    Route::get("tareas/config", [TareasController::class, 'config']);
    Route::post('/tareas/{id}/move', [TareasController::class, 'move']);
    Route::resource("tareas", TareasController::class);

    // Timeline y comentarios
    Route::get('/tareas/{tareaId}/timeline', [ComentariosController::class, 'index']);
    Route::post('/tareas/{tareaId}/comentarios', [ComentariosController::class, 'store']);
    Route::put('/tareas/{tareaId}/comentarios/{comentarioId}', [ComentariosController::class, 'update']);
    Route::delete('/tareas/{tareaId}/comentarios/{comentarioId}', [ComentariosController::class, 'destroy']);

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

    // Adjuntos de tareas
    Route::get('/tareas/{tareaId}/adjuntos', [TareaAdjuntosController::class, 'index']);
    Route::post('/tareas/{tareaId}/adjuntos', [TareaAdjuntosController::class, 'store']);
    Route::delete('/tareas/{tareaId}/adjuntos/{adjuntoId}', [TareaAdjuntosController::class, 'destroy']);

    // Miembros de tareas
    Route::post('/tareas/{tarea}/assign-members', [TareasController::class, 'assignMembers']);
    Route::get('/tareas/{tarea}/members', [TareasController::class, 'getMembers']);
    Route::delete('/tareas/{tarea}/unassign-member/{user}', [TareasController::class, 'unassignMember']);


    // ========================================
    // 📊 GANTT CHART
    // ========================================
    Route::get('/gantt/data', [GanttController::class, 'getGanttData']);
    Route::get('/gantt/filter-options', [GanttController::class, 'getFilterOptions']);


    // ========================================
    // 📄 DOCUMENTOS
    // ========================================
    Route::get('/documentos', [DocumentosController::class, 'index']);
    Route::post('/documentos', [DocumentosController::class, 'store']);
    Route::get('/documentos/config', [DocumentosController::class, 'config']);
    Route::put('/documentos/{id}', [DocumentosController::class, 'update']);
    Route::delete('/documentos/{id}', [DocumentosController::class, 'destroy']);

    // Carpetas
    Route::get('/documentos/tree', [DocumentosController::class, 'getTree']);
    Route::post('/documentos/folder', [DocumentosController::class, 'createFolder']);
    Route::get('/documentos/folder/{id}', [DocumentosController::class, 'getFolderContents']);
    Route::post('/documentos/{id}/move', [DocumentosController::class, 'move']);

    // Descarga y visualización
    Route::get('/documentos/{id}/download', [DocumentosController::class, 'download']);
    Route::get('/documentos/{id}/info', [DocumentosController::class, 'getDocumentInfo']);

    // ========================================
    // 📊 ACTIVIDADES
    // ========================================
    Route::get('/activities', [ActivityController::class, 'index']);
    Route::get('/activities/tarea/{tareaId}', [ActivityController::class, 'getByTarea']);
    Route::post('/activities', [ActivityController::class, 'store']);
    Route::delete('/activities/{id}', [ActivityController::class, 'destroy']);

    // ========================================
    // 🔔 NOTIFICACIONES
    // ========================================
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications/delete-read', [NotificationController::class, 'deleteAllRead']);
    Route::get('/notifications/debug', [NotificationController::class, 'debug']);

    // ========================================
    // 👤 PERFIL
    // ========================================
    Route::get('/profile/tareas', [ProfileController::class, 'getUserTareas']);
    Route::get('/profile/documentos', [ProfileController::class, 'getUserDocumentos']);
    Route::get('/profile/stats', [ProfileController::class, 'getUserStats']);
    Route::get('/profile/complete', [ProfileController::class, 'getCompleteProfile']);
});

// Ruta pública para OnlyOffice (sin token)
Route::post('/documentos/{id}/save-callback', [DocumentosController::class, 'saveDocument']);