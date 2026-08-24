<?php

namespace App\Http\Controllers\Api;

use App\Enums\DocumentVisibility;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\AuditLog;
use App\Models\Condominium;
use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentPublished;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $request->validate(['condominium_id' => ['required', 'integer', 'exists:condominiums,id']]);

        $condominium = Condominium::findOrFail($request->integer('condominium_id'));
        $this->authorize('view', $condominium);

        $query = $condominium->documents()->with(['category', 'uploader']);

        if ($user->role === UserRole::Condomino) {
            $query->whereIn('visibility', [DocumentVisibility::All->value, DocumentVisibility::Condomini->value]);
        }

        $documents = $query
            ->when($request->filled('document_category_id'), fn ($q) => $q->where('document_category_id', $request->integer('document_category_id')))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
            ->orderByDesc('published_at')
            ->paginate($request->integer('per_page', 20));

        return DocumentResource::collection($documents);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $condominium = Condominium::findOrFail($request->validated('condominium_id'));

        $file = $request->file('file');
        $path = $file->storeAs('documents', Str::uuid().'.'.$file->getClientOriginalExtension(), 'local');

        $document = $condominium->documents()->create([
            'document_category_id' => $request->validated('document_category_id'),
            'uploaded_by' => $request->user()->id,
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'visibility' => $request->validated('visibility'),
            'published_at' => now(),
        ]);

        $recipients = User::whereHas('units', fn ($q) => $q->where('units.condominium_id', $condominium->id))->get();
        Notification::send($recipients, new DocumentPublished($document));

        AuditLog::record('document.created', $document, [], $request->safe()->except('file'), $condominium->id);

        return response()->json(['data' => new DocumentResource($document->load(['category', 'uploader']))], 201);
    }

    public function show(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        return response()->json(['data' => new DocumentResource($document->load(['category', 'uploader']))]);
    }

    public function download(Request $request, Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function destroy(Request $request, Document $document): JsonResponse
    {
        $this->authorize('delete', $document);

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return response()->json(null, 204);
    }
}
