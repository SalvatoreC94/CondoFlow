<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketAttachmentRequest;
use App\Http\Resources\TicketAttachmentResource;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    public function store(StoreTicketAttachmentRequest $request, Ticket $ticket): JsonResponse
    {
        $file = $request->file('file');
        $path = $file->storeAs(
            "tickets/{$ticket->id}",
            Str::uuid().'.'.$file->getClientOriginalExtension(),
            'local'
        );

        $attachment = $ticket->attachments()->create([
            'uploaded_by' => $request->user()->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json(['data' => new TicketAttachmentResource($attachment->load('uploader'))], 201);
    }

    public function download(Request $request, Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        $this->authorize('view', $ticket);
        abort_if($attachment->ticket_id !== $ticket->id, 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function destroy(Request $request, Ticket $ticket, TicketAttachment $attachment): JsonResponse
    {
        $this->authorize('deleteAttachment', $ticket);
        abort_if($attachment->ticket_id !== $ticket->id, 404);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return response()->json(null, 204);
    }
}
