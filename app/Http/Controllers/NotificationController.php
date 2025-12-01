<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

            $limit = $request->input('limit', 20);
            $unreadOnly = $request->input('unread_only', false);

            $query = Notification::with([
                'fromUser:id,name,surname,avatar',
                'tarea:id,name',
                'grupo:id,name'
            ])->where('user_id', $user->id);

            if ($unreadOnly) {
                $query->unread();
            }

            $notifications = $query->recent($limit)->get()->map(function($notification) {
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
                    'is_read' => $notification->is_read,
                    'read_at' => $notification->read_at ? $notification->read_at->format('Y-m-d H:i:s') : null,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'created_at_full' => $notification->created_at->format('Y-m-d H:i:s'),
                ];
            });

            // Contar notificaciones no leídas
            $unreadCount = Notification::where('user_id', $user->id)->unread()->count();

            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'total' => $notifications->count(),
                'unread_count' => $unreadCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener notificaciones: ' . $e->getMessage(), [
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

            $unreadCount = Notification::where('user_id', $user->id)->unread()->count();

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
     * Marcar una notificación como leída
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

            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al marcar notificación: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar notificación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar todas las notificaciones como leídas
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
            
            Notification::where('user_id', $user->id)
                ->unread()
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Todas las notificaciones marcadas como leídas'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al marcar notificaciones: ' . $e->getMessage());
            
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

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notificación eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar notificación: ' . $e->getMessage());
            
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
            
            Notification::where('user_id', $user->id)
                ->read()
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notificaciones leídas eliminadas exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar notificaciones: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar notificaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}