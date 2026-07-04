<?php

namespace App\Http\Controllers\Api\Owner;

use App\Enums\ReporterRole;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketCommentController extends Controller
{
    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        abort_if(
            $ticket->unit->property->owner_id !== $request->user()->id,
            403
        );

        $data = $request->validate([
            'body' => 'required|string',
        ]);

        $comment = $ticket->comments()->create([
            'author_id'   => $request->user()->id,
            'author_role' => ReporterRole::OWNER,
            'body'        => $data['body'],
        ]);

        return response()->json($comment->load('author'), 201);
    }
}
