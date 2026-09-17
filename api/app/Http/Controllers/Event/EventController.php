<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\Event\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Lista todos os eventos.
     */
    public function index()
    {
        // Ordena os eventos pelos mais recentes/próximos primeiro
        return response()->json(Event::orderBy('start_time', 'desc')->paginate(15));
    }

    /**
     * Cria um novo evento.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'location'    => 'nullable|string|max:255',
            'start_time'  => 'required|date',
            'end_time'    => 'nullable|date|after_or_equal:start_time',
        ]);

        $event = Event::create($validated);

        return response()->json([
            'message' => 'Evento criado com sucesso.',
            'data'    => $event
        ], 201);
    }

    /**
     * Mostra os detalhes de um evento específico.
     */
    public function show(Event $event)
    {
        // Carrega os convites associados a este evento
        $event->load('invites');
        
        return response()->json($event);
    }

    /**
     * Atualiza um evento existente.
     */
    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'location'    => 'nullable|string|max:255',
            'start_time'  => 'sometimes|date',
            'end_time'    => 'nullable|date|after_or_equal:start_time',
        ]);

        $event->update($validated);

        return response()->json([
            'message' => 'Evento atualizado com sucesso.',
            'data'    => $event
        ]);
    }

    /**
     * Remove um evento.
     */
    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json([
            'message' => 'Evento apagado com sucesso.'
        ]);
    }
}