<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Enums\ReporterRole;
use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tickets = Ticket::with(['unit.property', 'comments.author'])
            ->where('reporter_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($tickets);
    }

    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        abort_if($ticket->reporter_id !== $request->user()->id, 403);

        return response()->json($ticket->load(['unit.property', 'comments.author']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category'    => 'required|in:plumbing,electrical,appliance,structural,pest,other',
            'priority'    => 'required|in:low,medium,high,urgent',
            'title'       => 'required|string|max:100',
            'description' => 'required|string',
        ]);

        // Derive the unit from the tenant's active agreement
        $agreement = Agreement::where('tenant_id', $request->user()->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        abort_if(! $agreement, 422, 'No active agreement — cannot file a ticket.');

        $ticket = Ticket::create(array_merge($data, [
            'unit_id'       => $agreement->unit_id,
            'reporter_id'   => $request->user()->id,
            'reporter_role' => ReporterRole::TENANT,
        ]));

        return response()->json($ticket->load(['unit.property', 'reporter']), 201);
    }

    public function addComment(Request $request, Ticket $ticket): JsonResponse
    {
        abort_if($ticket->reporter_id !== $request->user()->id, 403);

        $data = $request->validate([
            'body' => 'required|string',
        ]);

        $comment = $ticket->comments()->create([
            'author_id'   => $request->user()->id,
            'author_role' => ReporterRole::TENANT,
            'body'        => $data['body'],
        ]);

        return response()->json($comment->load('author'), 201);
    }
}
