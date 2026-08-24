<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketCommentRequest;
use App\Http\Resources\TicketCommentResource;
use App\Models\Ticket;
use App\Services\TicketWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketCommentController extends Controller
{
    public function __construct(private readonly TicketWorkflow $workflow) {}

    public function index(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $canSeeInternal = $request->user()->can('viewInternalComments', $ticket);

        $comments = $ticket->comments()
            ->with('user')
            ->when(! $canSeeInternal, fn ($q) => $q->where('is_internal', false))
            ->orderBy('created_at')
            ->get();

        return response()->json(['data' => TicketCommentResource::collection($comments)]);
    }

    public function store(StoreTicketCommentRequest $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();
        $isInternal = $user->can('viewInternalComments', $ticket) && $request->boolean('is_internal');

        $comment = $this->workflow->addComment($ticket, $user, $request->validated('body'), $isInternal);

        return response()->json(['data' => new TicketCommentResource($comment->load('user'))], 201);
    }
}
