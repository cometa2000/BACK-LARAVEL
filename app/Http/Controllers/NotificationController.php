<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Obtener todas las notificaciones del usuario autenticado
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // 🔍 DEBUG: Verificar qué usuario está autenticado
            Log::info('🔍 Usuario autenticado:', ['user_id' => $user->id, 'name' => $user->name]);

            $limit = $request->input('limit', 20);
            $unreadOnly = $request->input('unread_only', false);

            // 🔍 DEBUG: Contar notificaciones en BD para este usuario
            $totalInDB = Notification::where('user_id', $user->id)->count();
            $unreadInDB = Notification::where('user_id', $user->id)->where('is_read', false)->count();
            Log::info('🔍 Notificaciones en BD:', [
                'total' => $totalInDB,
                'unread' => $unreadInDB,
                'user_id' => $user->id
            ]);

            // ✅ CORRECCIÓN: No usar withTrashed() para incluir eliminadas, solo las activas
            $query = Notification::with([
                'fromUser:id,name,surname,avatar',
                'tarea:id,name',
                'grupo:id,name'
            ])->where('user_id', $user->id);

            if ($unreadOnly) {
                $query->where('is_read', false);
            }

            // ✅ Ordenar por fecha de creación descendente y limitar
            $notifications = $query->orderBy('created_at', 'desc')
                                  ->limit($limit)
                                  ->get()
                                  ->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'from_user' => $notification->fromUser ? [
                        'id' => $notification->fromUser->id,
                        'name' => trim(($notification->fromUser->name ?? '') . ' ' . ($notification->fromUser->surname ?? '')),
                        'avatar' => $notification->fromUser->avatar ?? '/media/avatars/blank.png',
                    ] : null,
                    'tarea' => $notification->tarea ? [
                        'id' => $notification->tarea->id,
                        'title' => $notification->tarea->name,
                    ] : null,
                    'grupo' => $notification->grupo ? [
                        'id' => $notification->grupo->id,
                        'name' => $notification->grupo->name,
                    ] : null,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'data' => $notification->data,
                    'icon' => $notification->getIcon(),
                    'color' => $notification->getColor(),
                    'is_read' => (bool)$notification->is_read,
                    'read_at' => $notification->read_at ? $notification->read_at->format('Y-m-d H:i:s') : null,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'created_at_full' => $notification->created_at->format('Y-m-d H:i:s'),
                ];
            });

            // Contar notificaciones no leídas
            $unreadCount = Notification::where('user_id', $user->id)
                                      ->where('is_read', false)
                                      ->count();

            Log::info('✅ Notificaciones devueltas:', [
                'count' => $notifications->count(),
                'unread_count' => $unreadCount
            ]);

            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'total' => $notifications->count(),
                'unread_count' => $unreadCount,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener notificaciones: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener notificaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener el contador de notificaciones no leídas
     */
    public function getUnreadCount()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'unread_count' => 0,
                ], 401);
            }

            $unreadCount = Notification::where('user_id', $user->id)
                                      ->where('is_read', false)
                                      ->count();

            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener contador: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener contador',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Marcar una notificación como leída SIN ELIMINARLA
     */
    public function markAsRead($id)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $notification = Notification::where('user_id', $user->id)->findOrFail($id);

            // 🔍 DEBUG: Antes de actualizar
            Log::info('🔍 Antes de marcar como leída:', [
                'notification_id' => $notification->id,
                'is_read' => $notification->is_read,
                'user_id' => $user->id
            ]);

            // ✅ CRÍTICO: Solo actualizar is_read y read_at, NO eliminar
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            // 🔍 DEBUG: Después de actualizar
            $notification->refresh();
            Log::info('✅ Después de marcar como leída:', [
                'notification_id' => $notification->id,
                'is_read' => $notification->is_read,
                'read_at' => $notification->read_at
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída',
                'notification' => [
                    'id' => $notification->id,
                    'is_read' => true,
                    'read_at' => $notification->read_at->format('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al marcar notificación: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar notificación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Marcar todas las notificaciones como leídas SIN ELIMINARLAS
     */
    public function markAllAsRead()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // 🔍 DEBUG: Antes de actualizar
            $countBefore = Notification::where('user_id', $user->id)
                                      ->where('is_read', false)
                                      ->count();
            Log::info('🔍 Antes de marcar todas como leídas:', [
                'user_id' => $user->id,
                'unread_count' => $countBefore
            ]);
            
            // ✅ CRÍTICO: Solo actualizar is_read y read_at, NO eliminar
            $updated = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            // 🔍 DEBUG: Después de actualizar
            $totalAfter = Notification::where('user_id', $user->id)->count();
            $unreadAfter = Notification::where('user_id', $user->id)
                                      ->where('is_read', false)
                                      ->count();
            Log::info('✅ Después de marcar todas como leídas:', [
                'updated' => $updated,
                'total_notifications' => $totalAfter,
                'unread_count' => $unreadAfter
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Todas las notificaciones marcadas como leídas',
                'updated' => $updated
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al marcar notificaciones: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar notificaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una notificación
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $notification = Notification::where('user_id', $user->id)->findOrFail($id);

            Log::info('🗑️ Eliminando notificación:', [
                'notification_id' => $notification->id,
                'user_id' => $user->id
            ]);

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notificación eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar notificación: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar notificación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar todas las notificaciones leídas
     */
    public function deleteAllRead()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $count = Notification::where('user_id', $user->id)
                                ->where('is_read', true)
                                ->count();
            
            Log::info('🗑️ Eliminando notificaciones leídas:', [
                'user_id' => $user->id,
                'count' => $count
            ]);
            
            Notification::where('user_id', $user->id)
                ->where('is_read', true)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notificaciones leídas eliminadas exitosamente',
                'deleted' => $count
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar notificaciones: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar notificaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🆕 NUEVO: Endpoint para debugging - ver todas las notificaciones en BD
     */
    public function debug()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $allNotifications = Notification::where('user_id', $user->id)
                                           ->orderBy('created_at', 'desc')
                                           ->get();

            $stats = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'total' => $allNotifications->count(),
                'unread' => $allNotifications->where('is_read', false)->count(),
                'read' => $allNotifications->where('is_read', true)->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'notifications' => $allNotifications->map(function($n) {
                    return [
                        'id' => $n->id,
                        'type' => $n->type,
                        'title' => $n->title,
                        'is_read' => (bool)$n->is_read,
                        'read_at' => $n->read_at ? $n->read_at->format('Y-m-d H:i:s') : null,
                        'created_at' => $n->created_at->format('Y-m-d H:i:s'),
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Error en debug: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}