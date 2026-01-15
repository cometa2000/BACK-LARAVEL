<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CalendarEventController extends Controller
{
    /**
     * Display a listing of the events.
     */
    public function index(Request $request)
    {
        try {
            $events = CalendarEvent::with('creator')
                ->orderBy('start_date', 'asc')
                ->get()
                ->map(function ($event) {
                    return $event->toFullCalendarFormat();
                });

            return response()->json([
                'message' => 200,
                'events' => $events,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created event.
     */
    public function store(Request $request)
    {
        try {
            // Verificar que el usuario tenga permisos (Super Admin o ID = 1)
            $user = auth()->user();
            if ($user->id !== 1 && $user->name !== 'Super Admin') {
                return response()->json([
                    'message' => 403,
                    'error' => 'No tienes permisos para crear eventos.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'all_day' => 'boolean',
                'color' => 'nullable|string|max:7',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $event = CalendarEvent::create([
                'title' => $request->title,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'all_day' => $request->all_day ?? false,
                'color' => $request->color ?? '#3788d8',
                'created_by' => $user->id,
            ]);

            return response()->json([
                'message' => 201,
                'event' => $event->toFullCalendarFormat(),
                'text' => 'Evento creado exitosamente.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified event.
     */
    public function show($id)
    {
        try {
            $event = CalendarEvent::with('creator')->findOrFail($id);

            return response()->json([
                'message' => 200,
                'event' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'start_date' => $event->start_date->format('Y-m-d\TH:i'),
                    'end_date' => $event->end_date ? $event->end_date->format('Y-m-d\TH:i') : null,
                    'all_day' => $event->all_day,
                    'color' => $event->color,
                    'created_by' => $event->created_by,
                    'creator_name' => $event->creator ? $event->creator->name : 'Usuario',
                    'created_at' => $event->created_at->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 404,
                'error' => 'Evento no encontrado.',
            ], 404);
        }
    }

    /**
     * Update the specified event.
     */
    public function update(Request $request, $id)
    {
        try {
            // Verificar que el usuario tenga permisos (Super Admin o ID = 1)
            $user = auth()->user();
            if ($user->id !== 1 && $user->name !== 'Super Admin') {
                return response()->json([
                    'message' => 403,
                    'error' => 'No tienes permisos para editar eventos.',
                ], 403);
            }

            $event = CalendarEvent::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'all_day' => 'boolean',
                'color' => 'nullable|string|max:7',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $event->update([
                'title' => $request->title,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'all_day' => $request->all_day ?? false,
                'color' => $request->color ?? $event->color,
            ]);

            return response()->json([
                'message' => 200,
                'event' => $event->toFullCalendarFormat(),
                'text' => 'Evento actualizado exitosamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified event.
     */
    public function destroy($id)
    {
        try {
            // Verificar que el usuario tenga permisos (Super Admin o ID = 1)
            $user = auth()->user();
            if ($user->id !== 1 && $user->name !== 'Super Admin') {
                return response()->json([
                    'message' => 403,
                    'error' => 'No tienes permisos para eliminar eventos.',
                ], 403);
            }

            $event = CalendarEvent::findOrFail($id);
            $event->delete();

            return response()->json([
                'message' => 200,
                'text' => 'Evento eliminado exitosamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}