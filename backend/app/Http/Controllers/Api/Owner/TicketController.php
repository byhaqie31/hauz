<?php

namespace App\Http\Controllers\Api\Owner;

use App\Enums\ReporterRole;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tickets = Ticket::with(['unit.property', 'reporter', 'comments.author'])
            ->whereHas('unit.property', fn ($q) =>
                $q->where('owner_id', $request->user()->id)
            )
            ->latest()
            ->get();

        return response()->json($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'unit_id'     => 'required|uuid|exists:units,id',
            'category'    => 'required|in:plumbing,electrical,appliance,structural,pest,other',
            'priority'    => 'required|in:low,medium,high,urgent',
            'title'       => 'required|string|max:100',
            'description' => 'required|string',
        ]);

        $unit = Unit::findOrFail($data['unit_id']);
        abort_if($unit->property->owner_id !== $request->user()->id, 403);

        $ticket = Ticket::create(array_merge($data, [
            'reporter_id'   => $request->user()->id,
            'reporter_role' => ReporterRole::OWNER,
        ]));

        return response()->json($ticket->load(['unit.property', 'reporter']), 201);
    }

    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeOwner($request, $ticket);

        return response()->json($ticket->load(['unit.property', 'reporter', 'comments.author']));
    }

    public function update(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeOwner($request, $ticket);

        $data = $request->validate([
            'category'    => 'sometimes|in:plumbing,electrical,appliance,structural,pest,other',
            'priority'    => 'sometimes|in:low,medium,high,urgent',
            'title'       => 'sometimes|string|max:100',
            'description' => 'sometimes|string',
        ]);

        $ticket->update($data);

        return response()->json($ticket->load(['unit.property', 'reporter', 'comments.author']));
    }

    public function destroy(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeOwner($request, $ticket);

        $ticket->delete();

        return response()->json(null, 204);
    }

    public function updateStatus(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeOwner($request, $ticket);

        $data = $request->validate([
            'status' => 'required|in:new,in_progress,resolved,reopened',
        ]);

        $next = TicketStatus::from($data['status']);

        if (! $ticket->canTransitionTo($next)) {
            return response()->json([
                'message' => "Cannot transition from {$ticket->status->value} to {$next->value}.",
            ], 422);
        }

        $ticket->update([
            'status'      => $next,
            'resolved_at' => $next === TicketStatus::RESOLVED ? now() : $ticket->resolved_at,
        ]);

        return response()->json($ticket);
    }

    private function authorizeOwner(Request $request, Ticket $ticket): void
    {
        abort_if(
            $ticket->unit->property->owner_id !== $request->user()->id,
            403
        );
    }
}
