<?php

namespace App\Http\Controllers\sistema_de_tickets;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\sistema_de_tickets\Ticket;
use App\Models\sistema_de_tickets\TicketMessage;
use App\Models\sistema_de_tickets\TicketAttachment;
use App\Models\sistema_de_tickets\TicketStatusHistory;
use App\Models\sistema_de_tickets\TicketAssignment;
use App\Models\sistema_de_tickets\TicketFavorito;
use App\Models\sistema_de_tickets\TicketArchivado;
use App\Models\User;
use App\Models\Configuration\Sucursale;
use Spatie\Permission\Models\Role;
use App\Models\documents\Documentos;
use App\Models\sistema_de_tickets\TicketTarea;
use App\Models\tasks\Tareas;
use App\Models\tasks\Grupos;

class TicketsController extends Controller
{
    // ================================================================
    // CONSTANTES
    // ================================================================
    const SEDE_PRINCIPAL_ID = 5; // ID de la sucursal "huezo" (sede principal)

    // ================================================================
    // INDEX - Listar tickets con filtros de vista
    // ================================================================
    public function index(Request $request)
    {
        try {
            $user = auth('api')->user();
            $vista = $request->get('vista', 'bandeja'); // bandeja|enviados|en_proceso|finalizados|archivados|favoritos

            $query = Ticket::with([
                'creador:id,name,surname,avatar',
                'asignado:id,name,surname,avatar',
                'sucursale:id,name',
                'sucursalDestino:id,name',
                'rolDestino:id,name',
            ])->withCount('messages');

            $esSede = $user->sucursale_id == self::SEDE_PRINCIPAL_ID;

            // ── Filtro por vista ──
            switch ($vista) {
                case 'bandeja':
                    $query->where('asignado_id', $user->id)
                          ->whereNotIn('estado', ['cerrado', 'rechazado']);
                    if ($this->pivotTablesExist()) {
                        $query->whereDoesntHave('archivadosPor', fn($q) => $q->where('user_id', $user->id));
                    } else {
                        $query->where('archivado', false);
                    }
                    break;

                case 'enviados':
                    $query->where('creador_id', $user->id);
                    if ($this->pivotTablesExist()) {
                        $query->whereDoesntHave('archivadosPor', fn($q) => $q->where('user_id', $user->id));
                    } else {
                        $query->where('archivado', false);
                    }
                    break;

                case 'en_proceso':
                    $query->where('estado', 'en_proceso');
                    if ($this->pivotTablesExist()) {
                        $query->whereDoesntHave('archivadosPor', fn($q) => $q->where('user_id', $user->id));
                    } else {
                        $query->where('archivado', false);
                    }
                    if (!$esSede) {
                        // Sucursal: solo los suyos
                        $query->where(function($q) use ($user) {
                            $q->where('creador_id', $user->id)
                              ->orWhere('asignado_id', $user->id);
                        });
                    }
                    break;

                case 'finalizados':
                    $query->whereIn('estado', ['resuelto', 'cerrado']);
                    if ($this->pivotTablesExist()) {
                        $query->whereDoesntHave('archivadosPor', fn($q) => $q->where('user_id', $user->id));
                    } else {
                        $query->where('archivado', false);
                    }
                    if (!$esSede) {
                        $query->where(function($q) use ($user) {
                            $q->where('creador_id', $user->id)
                              ->orWhere('asignado_id', $user->id);
                        });
                    }
                    break;

                case 'archivados':
                    if ($this->pivotTablesExist()) {
                        $query->whereHas('archivadosPor', fn($q) => $q->where('user_id', $user->id));
                    } else {
                        $query->where('archivado', true)
                              ->where(fn($q) => $q->where('creador_id', $user->id)->orWhere('asignado_id', $user->id));
                    }
                    break;

                case 'favoritos':
                    if ($this->pivotTablesExist()) {
                        $query->whereHas('favoritosPor', fn($q) => $q->where('user_id', $user->id));
                    } else {
                        $query->where('es_favorito', true)
                              ->where(fn($q) => $q->where('creador_id', $user->id)->orWhere('asignado_id', $user->id));
                    }
                    break;

                default:
                    $query->where(function($q) use ($user) {
                        $q->where('creador_id', $user->id)
                          ->orWhere('asignado_id', $user->id);
                    });
            }

            // ── Filtros adicionales opcionales ──
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }
            if ($request->filled('prioridad')) {
                $query->where('prioridad', $request->prioridad);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('asunto', 'like', "%{$search}%")
                      ->orWhere('folio', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }

            $query->orderBy('created_at', 'desc');

            $tickets = $query->paginate(20);

            return response()->json([
                'message' => 200,
                'total'   => $tickets->total(),
                'tickets' => $tickets->map(fn($t) => $this->formatTicketResumen($t, $user))->values(),
            ]);

        } catch (\Exception $e) {
            Log::error('TicketsController@index', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 500, 'error' => 'Error al obtener tickets'], 500);
        }
    }

    // ================================================================
    // SHOW - Detalle completo del ticket con conversación
    // ================================================================
    public function show(string $id)
    {
        try {
            $ticket = Ticket::with([
                'creador:id,name,surname,avatar,email',
                'asignado:id,name,surname,avatar,email',
                'sucursale:id,name',
                'sucursalDestino:id,name',
                'rolDestino:id,name',
                'messages.user:id,name,surname,avatar',
                'messages.attachments',
                'attachments',
                'statusHistory.user:id,name,surname,avatar',
                'assignments.asignadoPor:id,name,surname',
                'assignments.asignadoA:id,name,surname',
            ])->findOrFail($id);

            return response()->json([
                'message' => 200,
                'ticket'  => $this->formatTicketDetalle($ticket, auth('api')->user()),
            ]);

        } catch (\Exception $e) {
            Log::error('TicketsController@show', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al obtener el ticket'], 500);
        }
    }

    // ================================================================
    // STORE - Crear nuevo ticket
    // ================================================================
    public function store(Request $request)
    {
        try {
            $user = auth('api')->user();
            $esSede = $user->sucursale_id == self::SEDE_PRINCIPAL_ID;

            // ── Validaciones ──
            $request->validate([
                'asunto'      => 'required|string|max:255',
                'descripcion' => 'required|string',
                'prioridad'   => 'in:baja,media,alta',
                'fecha_limite'=> 'nullable|date',
                'categoria'   => 'nullable|string|max:100',
            ]);

            // ── Determinar tipo_origen y destino ──
            $tipoOrigen = $esSede ? 'sede' : 'sucursal';
            $tipoDestino = null;
            $rolDestinoId = null;
            $sucursalDestinoId = null;
            $asignadoId = null;

            if ($esSede) {
                // Sede → Sucursal: asignar al franquiciatario
                $request->validate(['sucursal_destino_id' => 'required|exists:sucursales,id']);
                $tipoDestino = 'sucursal';
                $sucursalDestinoId = $request->sucursal_destino_id;

                // Buscar franquiciatario de esa sucursal (rol_id = 3 o según configuración)
                $franquiciatario = User::where('sucursale_id', $sucursalDestinoId)
                    ->whereHas('roles', fn($q) => $q->where('name', 'Franquiciatario'))
                    ->first();
                $asignadoId = $franquiciatario?->id;

            } else {
                // Sucursal → Área de sede: asignar a un usuario de ese rol en sede
                $request->validate(['rol_destino_id' => 'required|exists:roles,id']);
                $tipoDestino = 'area_sede';
                $rolDestinoId = $request->rol_destino_id;

                // Buscar usuario disponible en sede con ese rol
                $responsable = User::where('sucursale_id', self::SEDE_PRINCIPAL_ID)
                    ->whereHas('roles', fn($q) => $q->where('id', $rolDestinoId))
                    ->first();
                $asignadoId = $responsable?->id;
            }

            // ── Crear ticket ──
            $ticket = Ticket::create([
                'folio'               => Ticket::generarFolio(),
                'creador_id'          => $user->id,
                'asignado_id'         => $asignadoId,
                'sucursale_id'        => $user->sucursale_id,
                'tipo_origen'         => $tipoOrigen,
                'tipo_destino'        => $tipoDestino,
                'rol_destino_id'      => $rolDestinoId,
                'sucursal_destino_id' => $sucursalDestinoId,
                'asunto'              => $request->asunto,
                'descripcion'         => $request->descripcion,
                'categoria'           => $request->categoria,
                'prioridad'           => $request->prioridad ?? 'media',
                'estado'              => 'pendiente',
                'fecha_limite'        => $request->fecha_limite,
            ]);

            // ── Registrar historial de estado inicial ──
            TicketStatusHistory::create([
                'ticket_id'       => $ticket->id,
                'user_id'         => $user->id,
                'estado_anterior' => null,
                'estado_nuevo'    => 'pendiente',
                'comentario'      => 'Ticket creado',
            ]);

            // ── TIPO 1: Archivos nuevos ──
            if ($request->hasFile('adjuntos')) {
                foreach ($request->file('adjuntos') as $archivo) {
                    $path = $archivo->store("tickets/{$ticket->id}", 'public');
                    TicketAttachment::create([
                        'ticket_id'  => $ticket->id,
                        'user_id'    => $user->id,
                        'nombre'     => $archivo->getClientOriginalName(),
                        'file_path'  => $path,
                        'mime_type'  => $archivo->getMimeType(),
                        'tamanio'    => $archivo->getSize(),
                    ]);
                }
            }

            // ── TIPO 2: Documentos existentes del sistema de archivos ──
            if ($request->has('documento_ids')) {
                foreach ($request->input('documento_ids', []) as $documentoId) {
                    $doc = Documentos::find($documentoId);
                    if (!$doc || $doc->type !== 'file') continue;
                    TicketAttachment::create([
                        'ticket_id'    => $ticket->id,
                        'user_id'      => $user->id,
                        'nombre'       => $doc->name,
                        'file_path'    => $doc->file_path,
                        'mime_type'    => $doc->mime_type ?? 'application/octet-stream',
                        'tamanio'      => $doc->size ?? 0,
                        'documento_id' => $doc->id,
                    ]);
                }
            }

            // ── TIPO 3: URLs externas ──
            if ($request->has('adjuntos_url')) {
                $urls = json_decode($request->input('adjuntos_url'), true) ?? [];
                foreach ($urls as $enlace) {
                    if (empty($enlace['url'])) continue;
                    TicketAttachment::create([
                        'ticket_id'      => $ticket->id,
                        'user_id'        => $user->id,
                        'nombre'         => $enlace['titulo'] ?? $enlace['url'],
                        'file_path'      => $enlace['url'],
                        'mime_type'      => 'text/url',
                        'tamanio'        => 0,
                        'es_url_externa' => true,
                    ]);
                }
            }

            $ticket->load(['creador:id,name,surname,avatar', 'asignado:id,name,surname,avatar', 'sucursale:id,name', 'sucursalDestino:id,name', 'rolDestino:id,name', 'attachments']);

            Log::info('Ticket creado', ['ticket_id' => $ticket->id, 'folio' => $ticket->folio]);

            return response()->json([
                'message' => 200,
                'ticket'  => $this->formatTicketResumen($ticket, auth('api')->user()),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 422, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('TicketsController@store', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 500, 'error' => 'Error al crear ticket'], 500);
        }
    }

    // ================================================================
    // UPDATE - Editar ticket
    // ================================================================
    public function update(Request $request, string $id)
    {
        try {
            $user   = auth('api')->user();
            $ticket = Ticket::findOrFail($id);

            // No editar tickets cerrados (salvo admin)
            if ($ticket->estado === 'cerrado' && !$user->hasRole('Administrador')) {
                return response()->json(['message' => 403, 'message_text' => 'No se puede editar un ticket cerrado'], 403);
            }

            $ticket->update([
                'asunto'       => $request->asunto       ?? $ticket->asunto,
                'descripcion'  => $request->descripcion  ?? $ticket->descripcion,
                'prioridad'    => $request->prioridad     ?? $ticket->prioridad,
                'fecha_limite' => $request->fecha_limite  ?? $ticket->fecha_limite,
                'categoria'    => $request->categoria     ?? $ticket->categoria,
            ]);

            // ── Nuevos adjuntos ──
            if ($request->hasFile('adjuntos')) {
                foreach ($request->file('adjuntos') as $archivo) {
                    $path = $archivo->store("tickets/{$ticket->id}", 'public');
                    TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'user_id'   => $user->id,
                        'nombre'    => $archivo->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $archivo->getMimeType(),
                        'tamanio'   => $archivo->getSize(),
                    ]);
                }
            }

            $ticket->load(['creador:id,name,surname,avatar', 'asignado:id,name,surname,avatar', 'sucursale:id,name', 'sucursalDestino:id,name', 'rolDestino:id,name', 'attachments']);

            return response()->json([
                'message' => 200,
                'ticket'  => $this->formatTicketResumen($ticket, auth('api')->user()),
            ]);

        } catch (\Exception $e) {
            Log::error('TicketsController@update', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al actualizar ticket'], 500);
        }
    }

    // ================================================================
    // DESTROY - Soft delete
    // ================================================================
    public function destroy(string $id)
    {
        try {
            $user   = auth('api')->user();
            $ticket = Ticket::findOrFail($id);

            // Solo el creador o admin puede eliminar
            if ($ticket->creador_id !== $user->id && !$user->hasRole('Administrador')) {
                return response()->json(['message' => 403, 'message_text' => 'Sin permiso para eliminar este ticket'], 403);
            }

            $ticket->delete();

            return response()->json(['message' => 200, 'message_text' => 'Ticket eliminado correctamente']);

        } catch (\Exception $e) {
            Log::error('TicketsController@destroy', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al eliminar ticket'], 500);
        }
    }

    // ================================================================
    // CAMBIAR ESTADO
    // ================================================================
    public function cambiarEstado(Request $request, string $id)
    {
        try {
            $user   = auth('api')->user();
            $ticket = Ticket::findOrFail($id);
            $esSede = $user->sucursale_id == self::SEDE_PRINCIPAL_ID;

            $request->validate([
                'estado'     => 'required|in:pendiente,en_proceso,en_espera,resuelto,cerrado,rechazado',
                'comentario' => 'nullable|string',
            ]);

            $nuevoEstado    = $request->estado;
            $estadoAnterior = $ticket->estado;

            // ── Permiso: SOLO el usuario asignado al ticket puede cambiar su estado ──
            if ((int)$ticket->asignado_id !== (int)$user->id) {
                return response()->json([
                    'message'      => 403,
                    'message_text' => 'Solo el responsable asignado puede cambiar el estado del ticket.',
                ], 403);
            }

            // Actualizar fechas automáticas
            $updates = ['estado' => $nuevoEstado];
            if ($nuevoEstado === 'en_proceso' && !$ticket->fecha_en_proceso) {
                $updates['fecha_en_proceso'] = now();
            }
            if ($nuevoEstado === 'resuelto' && !$ticket->fecha_resolucion) {
                $updates['fecha_resolucion'] = now();
            }
            if ($nuevoEstado === 'cerrado' && !$ticket->fecha_cierre) {
                $updates['fecha_cierre'] = now();
            }

            $ticket->update($updates);

            // Registrar historial
            TicketStatusHistory::create([
                'ticket_id'       => $ticket->id,
                'user_id'         => $user->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo'    => $nuevoEstado,
                'comentario'      => $request->comentario,
            ]);

            return response()->json([
                'message' => 200,
                'message_text' => 'Estado actualizado correctamente',
                'estado'  => $nuevoEstado,
            ]);

        } catch (\Exception $e) {
            Log::error('TicketsController@cambiarEstado', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al cambiar estado'], 500);
        }
    }

    // ================================================================
    // REASIGNAR
    // ================================================================
    public function reasignar(Request $request, string $id)
    {
        try {
            $user   = auth('api')->user();
            $esSede = $user->sucursale_id == self::SEDE_PRINCIPAL_ID;

            if (!$esSede && !$user->hasRole('Administrador')) {
                return response()->json(['message' => 403, 'message_text' => 'Sin permiso para reasignar'], 403);
            }

            $request->validate([
                'asignado_id' => 'required|exists:users,id',
                'motivo'      => 'nullable|string',
            ]);

            $ticket = Ticket::findOrFail($id);

            TicketAssignment::create([
                'ticket_id'       => $ticket->id,
                'asignado_por_id' => $user->id,
                'asignado_a_id'   => $request->asignado_id,
                'motivo'          => $request->motivo,
            ]);

            $ticket->update(['asignado_id' => $request->asignado_id]);

            return response()->json(['message' => 200, 'message_text' => 'Ticket reasignado correctamente']);

        } catch (\Exception $e) {
            Log::error('TicketsController@reasignar', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al reasignar ticket'], 500);
        }
    }

    // ================================================================
    // TOGGLE FAVORITO / ARCHIVAR  —  INDIVIDUALES POR USUARIO
    // Usa tablas pivot: ticket_favoritos / ticket_archivados.
    // Cada usuario mantiene su propio estado independiente.
    // ================================================================
    public function toggleFavorito(string $id)
    {
        try {
            $user   = auth('api')->user();
            $ticket = Ticket::findOrFail($id);

            $registro = TicketFavorito::where('ticket_id', $ticket->id)
                                       ->where('user_id',   $user->id)
                                       ->first();
            if ($registro) {
                $registro->delete();
                $esFavorito = false;
            } else {
                TicketFavorito::create(['ticket_id' => $ticket->id, 'user_id' => $user->id]);
                $esFavorito = true;
            }

            return response()->json(['message' => 200, 'es_favorito' => $esFavorito]);
        } catch (\Exception $e) {
            Log::error('TicketsController@toggleFavorito', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al actualizar favorito'], 500);
        }
    }

    public function toggleArchivar(string $id)
    {
        try {
            $user   = auth('api')->user();
            $ticket = Ticket::findOrFail($id);

            $registro = TicketArchivado::where('ticket_id', $ticket->id)
                                        ->where('user_id',   $user->id)
                                        ->first();
            if ($registro) {
                $registro->delete();
                $archivado = false;
            } else {
                TicketArchivado::create(['ticket_id' => $ticket->id, 'user_id' => $user->id]);
                $archivado = true;
            }

            return response()->json(['message' => 200, 'archivado' => $archivado]);
        } catch (\Exception $e) {
            Log::error('TicketsController@toggleArchivar', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al archivar ticket'], 500);
        }
    }

    // ================================================================
    // MENSAJES (Conversación interna)
    // ================================================================
    public function storeMessage(Request $request, string $ticketId)
    {
        try {
            $user   = auth('api')->user();
            $ticket = Ticket::findOrFail($ticketId);

            if (in_array($ticket->estado, ['cerrado', 'rechazado'])) {
                return response()->json(['message' => 403, 'message_text' => 'No se pueden agregar mensajes a un ticket cerrado'], 403);
            }

            $request->validate(['contenido' => 'required|string']);

            $esSede = $user->sucursale_id == self::SEDE_PRINCIPAL_ID;

            $message = TicketMessage::create([
                'ticket_id'       => $ticket->id,
                'user_id'         => $user->id,
                'contenido'       => $request->contenido,
                'es_nota_interna' => $request->es_nota_interna && $esSede ? true : false,
            ]);

            // Registrar fecha de primera respuesta
            if (!$ticket->fecha_primera_respuesta && $ticket->creador_id !== $user->id) {
                $ticket->update(['fecha_primera_respuesta' => now()]);
            }

            // ── TIPO 1: Archivos nuevos (subidos por el usuario) ──
            if ($request->hasFile('adjuntos')) {
                foreach ($request->file('adjuntos') as $archivo) {
                    $path = $archivo->store("tickets/{$ticket->id}/messages", 'public');
                    TicketAttachment::create([
                        'ticket_id'         => $ticket->id,
                        'ticket_message_id' => $message->id,
                        'user_id'           => $user->id,
                        'nombre'            => $archivo->getClientOriginalName(),
                        'file_path'         => $path,
                        'mime_type'         => $archivo->getMimeType(),
                        'tamanio'           => $archivo->getSize(),
                    ]);
                }
            }

            // ── TIPO 2: Documentos existentes del sistema de archivos (sin clonar) ──
            if ($request->has('documento_ids')) {
                $documentoIds = $request->input('documento_ids', []);
                foreach ($documentoIds as $documentoId) {
                    $doc = Documentos::find($documentoId);
                    if (!$doc || $doc->type !== 'file') continue;

                    TicketAttachment::create([
                        'ticket_id'         => $ticket->id,
                        'ticket_message_id' => $message->id,
                        'user_id'           => $user->id,
                        'nombre'            => $doc->name,
                        'file_path'         => $doc->file_path,   // Referencia al mismo path — sin clonar
                        'mime_type'         => $doc->mime_type ?? 'application/octet-stream',
                        'tamanio'           => $doc->size ?? 0,
                        'documento_id'      => $doc->id,          // FK opcional para rastrear origen
                    ]);
                }
            }

            // ── TIPO 3: URLs externas ──
            if ($request->has('adjuntos_url')) {
                $urls = json_decode($request->input('adjuntos_url'), true) ?? [];
                foreach ($urls as $enlace) {
                    if (empty($enlace['url'])) continue;
                    TicketAttachment::create([
                        'ticket_id'         => $ticket->id,
                        'ticket_message_id' => $message->id,
                        'user_id'           => $user->id,
                        'nombre'            => $enlace['titulo'] ?? $enlace['url'],
                        'file_path'         => $enlace['url'],    // La URL se guarda en file_path
                        'mime_type'         => 'text/url',
                        'tamanio'           => 0,
                        'es_url_externa'    => true,
                    ]);
                }
            }

            $message->load(['user:id,name,surname,avatar', 'attachments']);

            return response()->json([
                'message'  => 200,
                'mensaje'  => $this->formatMessage($message),
            ]);

        } catch (\Exception $e) {
            Log::error('TicketsController@storeMessage', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al enviar mensaje'], 500);
        }
    }

    // ================================================================
    // CONFIG - Datos necesarios para el formulario
    // ================================================================
    public function config()
    {
        try {
            $user   = auth('api')->user();
            $esSede = $user->sucursale_id == self::SEDE_PRINCIPAL_ID;

            if ($esSede) {
                // Sede ve todas las sucursales (menos la sede misma)
                $destinos = Sucursale::where('id', '!=', self::SEDE_PRINCIPAL_ID)
                                     ->get(['id', 'name']);
                return response()->json([
                    'tipo_usuario' => 'sede',
                    'es_sede'      => true,
                    'destinos'     => $destinos,
                ]);
            } else {
                // Sucursal ve únicamente los roles que tienen al menos un usuario
                // asignado a la sede principal — garantiza que el ticket vaya
                // a alguien real y no a un rol vacío
                $roles = Role::whereNotIn('name', ['Franquiciatario', 'Super-Admin'])
                    ->whereHas('users', function ($q) {
                        $q->where('sucursale_id', self::SEDE_PRINCIPAL_ID);
                    })
                    ->get(['id', 'name']);
                return response()->json([
                    'tipo_usuario' => 'sucursal',
                    'es_sede'      => false,
                    'destinos'     => $roles,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('TicketsController@config', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 500, 'error' => 'Error al obtener configuración: ' . $e->getMessage()], 500);
        }
    }

    // ================================================================
    // MÉTRICAS
    // ================================================================
    public function metricas()
    {
        try {
            $user   = auth('api')->user();
            $esSede = $user->sucursale_id == self::SEDE_PRINCIPAL_ID;

            // Base query: sede ve todo, sucursal solo ve los suyos
            $base = $esSede
                ? Ticket::query()
                : Ticket::where(function ($q) use ($user) {
                    $q->where('creador_id', $user->id)->orWhere('asignado_id', $user->id);
                  });

            // Conteos por vista del sidebar
            // Usar tabla pivot si ya fue migrada, o la columna legacy si no
            $usaPivot = $this->pivotTablesExist();
            $excluirArchivados = function($q) use ($user, $usaPivot) {
                if ($usaPivot) {
                    $q->whereDoesntHave('archivadosPor', fn($sq) => $sq->where('user_id', $user->id));
                } else {
                    $q->where('archivado', false);
                }
            };

            $bandeja     = (clone $base)->where('asignado_id', $user->id)
                                        ->whereNotIn('estado', ['cerrado', 'rechazado'])
                                        ->where($excluirArchivados)->count();

            $enviados    = (clone $base)->where('creador_id', $user->id)
                                        ->where($excluirArchivados)->count();

            $en_proceso  = (clone $base)->where('estado', 'en_proceso')
                                        ->where($excluirArchivados)->count();

            $finalizados = (clone $base)->whereIn('estado', ['resuelto', 'cerrado'])
                                        ->where($excluirArchivados)->count();

            $archivados  = $this->pivotTablesExist()
                ? (clone $base)->whereHas('archivadosPor', fn($q) => $q->where('user_id', $user->id))->count()
                : (clone $base)->where('archivado', true)->count();

            $favoritos   = $this->pivotTablesExist()
                ? (clone $base)->whereHas('favoritosPor', fn($q) => $q->where('user_id', $user->id))->count()
                : (clone $base)->where('es_favorito', true)->count();

            $vencidos    = (clone $base)->whereNotNull('fecha_limite')
                                        ->whereDate('fecha_limite', '<', now())
                                        ->whereNotIn('estado', ['cerrado', 'resuelto', 'rechazado'])
                                        ->count();

            return response()->json([
                'message'  => 200,
                'metricas' => [
                    'total'       => (clone $base)->count(),
                    // Conteos por vista (para badges del sidebar)
                    'bandeja'     => $bandeja,
                    'enviados'    => $enviados,
                    'en_proceso'  => $en_proceso,
                    'finalizados' => $finalizados,
                    'archivados'  => $archivados,
                    'favoritos'   => $favoritos,
                    'vencidos'    => $vencidos,
                    // Alias compatibles con código existente
                    'pendientes'  => (clone $base)->where('estado', 'pendiente')->count(),
                    'resueltos'   => (clone $base)->where('estado', 'resuelto')->count(),
                    'cerrados'    => (clone $base)->where('estado', 'cerrado')->count(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('TicketsController@metricas', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al obtener métricas'], 500);
        }
    }

    // ================================================================
    // HELPERS PRIVADOS
    // ================================================================

    /**
     * Indica si las tablas pivot de favoritos/archivados ya existen en la BD.
     * Permite degradar graciosamente si la migración está pendiente.
     */
    private function pivotTablesExist(): bool
    {
        static $checked = null;
        if ($checked !== null) return $checked;
        try {
            \Illuminate\Support\Facades\DB::table('ticket_favoritos')->limit(1)->get();
            $checked = true;
        } catch (\Exception $e) {
            $checked = false;
        }
        return $checked;
    }

    private function formatTicketResumen($ticket, $user = null): array
    {
        return [
            'id'              => $ticket->id,
            'folio'           => $ticket->folio,
            'asunto'          => $ticket->asunto,
            'descripcion'     => $ticket->descripcion,
            'categoria'       => $ticket->categoria,
            'prioridad'       => $ticket->prioridad,
            'estado'          => $ticket->estado,
            'tipo_origen'     => $ticket->tipo_origen,
            'tipo_destino'    => $ticket->tipo_destino,
            // Estado individual por usuario — con fallback por si las tablas pivot
            // aún no existen en el servidor (migración pendiente).
            'es_favorito'     => ($user && $this->pivotTablesExist())
                ? $ticket->favoritosPor()->where('user_id', $user->id)->exists()
                : (bool)($ticket->es_favorito ?? false),
            'archivado'       => ($user && $this->pivotTablesExist())
                ? $ticket->archivadosPor()->where('user_id', $user->id)->exists()
                : (bool)($ticket->archivado ?? false),
            'fecha_limite'    => $ticket->fecha_limite?->format('Y-m-d'),
            'fecha_cierre'    => $ticket->fecha_cierre?->format('Y-m-d H:i'),
            'is_vencido'      => $ticket->isVencido(),
            'messages_count'  => $ticket->messages_count ?? 0,
            'creador'         => $ticket->creador ? [
                'id'      => $ticket->creador->id,
                'nombre'  => $ticket->creador->name . ' ' . ($ticket->creador->surname ?? ''),
                'avatar'  => $ticket->creador->avatar,
            ] : null,
            'asignado'        => $ticket->asignado ? [
                'id'      => $ticket->asignado->id,
                'nombre'  => $ticket->asignado->name . ' ' . ($ticket->asignado->surname ?? ''),
                'avatar'  => $ticket->asignado->avatar,
            ] : null,
            'sucursal_origen' => $ticket->sucursale ? ['id' => $ticket->sucursale->id, 'nombre' => $ticket->sucursale->name] : null,
            'sucursal_destino'=> $ticket->sucursalDestino ? ['id' => $ticket->sucursalDestino->id, 'nombre' => $ticket->sucursalDestino->name] : null,
            'rol_destino'     => $ticket->rolDestino ? ['id' => $ticket->rolDestino->id, 'nombre' => $ticket->rolDestino->name] : null,
            'created_at'      => $ticket->created_at?->format('Y-m-d H:i:s'),
            'updated_at'      => $ticket->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function formatTicketDetalle($ticket, $user = null): array
    {
        $base = $this->formatTicketResumen($ticket, $user);

        $base['messages'] = $ticket->messages->map(fn($m) => $this->formatMessage($m));
        $base['attachments'] = $ticket->attachments->map(fn($a) => $this->formatAttachment($a));
        $base['status_history'] = $ticket->statusHistory->map(fn($h) => [
            'id'              => $h->id,
            'estado_anterior' => $h->estado_anterior,
            'estado_nuevo'    => $h->estado_nuevo,
            'comentario'      => $h->comentario,
            'usuario'         => $h->user ? $h->user->name . ' ' . ($h->user->surname ?? '') : null,
            'created_at'      => $h->created_at?->format('Y-m-d H:i:s'),
        ]);
        $base['assignments'] = $ticket->assignments->map(fn($a) => [
            'id'           => $a->id,
            'asignado_por' => $a->asignadoPor?->name . ' ' . ($a->asignadoPor?->surname ?? ''),
            'asignado_a'   => $a->asignadoA?->name . ' ' . ($a->asignadoA?->surname ?? ''),
            'motivo'       => $a->motivo,
            'created_at'   => $a->created_at?->format('Y-m-d H:i:s'),
        ]);
        $base['metricas_ticket'] = [
            'tiempo_primera_respuesta_min' => $ticket->getTiempoPrimeraRespuesta(),
            'tiempo_resolucion_horas'      => $ticket->getTiempoResolucion(),
        ];

        return $base;
    }

    private function formatMessage($message): array
    {
        return [
            'id'              => $message->id,
            'contenido'       => $message->contenido,
            'es_nota_interna' => $message->es_nota_interna,
            'user'            => $message->user ? [
                'id'     => $message->user->id,
                'nombre' => $message->user->name . ' ' . ($message->user->surname ?? ''),
                'avatar' => $message->user->avatar,
            ] : null,
            'adjuntos'   => $message->attachments ? $message->attachments->map(fn($a) => $this->formatAttachment($a)) : [],
            'created_at' => $message->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function formatAttachment($attachment): array
    {
        // Si es URL externa, file_path ya contiene la URL completa
        $esUrlExterna = $attachment->es_url_externa ?? ($attachment->mime_type === 'text/url');
        $fileUrl = $esUrlExterna
            ? $attachment->file_path
            : url('storage/' . $attachment->file_path);

        return [
            'id'             => $attachment->id,
            'nombre'         => $attachment->nombre,
            'mime_type'      => $attachment->mime_type,
            'tamanio'        => $attachment->tamanio,
            'file_url'       => $fileUrl,
            'es_url_externa' => $esUrlExterna,
            'documento_id'   => $attachment->documento_id ?? null,
        ];
    }

    public function tareasDisponibles()
    {
        try {
            $user = auth('api')->user();

            $tareas = Tareas::with(['lista:id,name', 'grupo:id,name,workspace_id', 'checklists'])
                ->where(function ($q) use ($user) {
                    // Tareas propias (creadas por el usuario)
                    $q->where('user_id', $user->id)
                      // O en grupos donde el usuario es dueño o tiene acceso compartido
                      ->orWhereHas('grupo', function ($gq) use ($user) {
                            $gq->where('user_id', $user->id)
                               ->orWhereHas('sharedUsers', fn($mq) => $mq->where('user_id', $user->id));
                        })
                      // O asignadas directamente al usuario
                      ->orWhereHas('assignedUsers', fn($aq) => $aq->where('user_id', $user->id));
                })
                ->whereNull('deleted_at')
                ->orderBy('name', 'asc')
                ->get();

            $data = $tareas->map(function ($tarea) {
                return [
                    'id'         => $tarea->id,
                    'name'       => $tarea->name,
                    'status'     => $tarea->status,
                    'priority'   => $tarea->priority,
                    'due_date'   => $tarea->due_date,
                    'grupo_id'   => $tarea->grupo_id,
                    'grupo_name' => $tarea->grupo?->name,
                    'lista_id'   => $tarea->lista_id,
                    'lista_name' => $tarea->lista?->name,
                    // progreso de checklists
                    'total_checklist_progress' => $tarea->getTotalChecklistProgress(),
                ];
            });

            return response()->json(['message' => 200, 'tareas' => $data]);

        } catch (\Exception $e) {
            \Log::error('TicketsController@tareasDisponibles', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al obtener tareas'], 500);
        }
    }

    public function adjuntarTarea(Request $request, $ticketId)
    {
        try {
            $user   = auth('api')->user();
            $ticket = Ticket::findOrFail($ticketId);

            $tareaId   = $request->input('tarea_id');
            $mensajeId = $request->input('ticket_message_id'); // nullable

            $tarea = Tareas::findOrFail($tareaId);

            $registro = TicketTarea::firstOrCreate(
                [
                    'ticket_id'         => $ticket->id,
                    'tarea_id'          => $tarea->id,
                    'ticket_message_id' => $mensajeId ?: null,
                ],
                ['user_id' => $user->id]
            );

            return response()->json([
                'message'         => 200,
                'ticket_tarea'    => $this->formatTicketTarea($registro->load('tarea')),
            ]);

        } catch (\Exception $e) {
            \Log::error('TicketsController@adjuntarTarea', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al adjuntar tarea'], 500);
        }
    }

    /**
     * DELETE /sistema-de-tickets/tickets/{ticketId}/adjuntar-tarea/{ticketTareaId}
     */
    public function quitarTarea($ticketId, $ticketTareaId)
    {
        try {
            $registro = TicketTarea::where('ticket_id', $ticketId)
                                    ->where('id', $ticketTareaId)
                                    ->firstOrFail();
            $registro->delete();

            return response()->json(['message' => 200]);

        } catch (\Exception $e) {
            \Log::error('TicketsController@quitarTarea', ['error' => $e->getMessage()]);
            return response()->json(['message' => 500, 'error' => 'Error al quitar tarea'], 500);
        }
    }

    // ── HELPER PRIVADO ───────────────────────────────────────────────
    private function formatTicketTarea(TicketTarea $tt): array
    {
        $tarea = $tt->tarea;
        return [
            'id'                      => $tt->id,
            'ticket_message_id'       => $tt->ticket_message_id,
            'tarea_id'                => $tarea?->id,
            'tarea_name'              => $tarea?->name,
            'tarea_status'            => $tarea?->status,
            'tarea_priority'          => $tarea?->priority,
            'tarea_due_date'          => $tarea?->due_date,
            'tarea_grupo_id'          => $tarea?->grupo_id,
            'tarea_progress'          => $tarea ? $tarea->getTotalChecklistProgress() : 0,
            'created_at'              => $tt->created_at,
        ];
    }
}