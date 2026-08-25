<?php

namespace App\Http\Controllers\Api;

use App\Enums\AssemblyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assembly\StoreAssemblyMinutesRequest;
use App\Http\Requests\Assembly\StoreAssemblyRequest;
use App\Http\Requests\Assembly\StoreAssemblyResolutionRequest;
use App\Http\Requests\Assembly\UpdateAssemblyRequest;
use App\Http\Resources\AssemblyResolutionResource;
use App\Http\Resources\AssemblyResource;
use App\Models\Assembly;
use App\Models\AssemblyResolution;
use App\Models\AuditLog;
use App\Models\Condominium;
use App\Models\DocumentCategory;
use App\Notifications\AssemblyScheduled;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class AssemblyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['condominium_id' => ['required', 'integer', 'exists:condominiums,id']]);

        $condominium = Condominium::findOrFail($request->integer('condominium_id'));
        $this->authorize('view', $condominium);

        $assemblies = $condominium->assemblies()
            ->with(['minutesDocument', 'resolutions'])
            ->orderByDesc('scheduled_at')
            ->paginate($request->integer('per_page', 20));

        return AssemblyResource::collection($assemblies);
    }

    public function store(StoreAssemblyRequest $request): JsonResponse
    {
        $condominium = Condominium::findOrFail($request->validated('condominium_id'));

        $assembly = $condominium->assemblies()->create([
            ...$request->safe()->except('condominium_id'),
            'created_by' => $request->user()->id,
            'status' => AssemblyStatus::Scheduled,
        ]);

        Notification::send($condominium->residents()->get(), new AssemblyScheduled($assembly));

        AuditLog::record('assembly.created', $assembly, [], $request->validated(), $condominium->id);

        return response()->json(['data' => new AssemblyResource($assembly->load('creator'))], 201);
    }

    public function show(Request $request, Assembly $assembly): JsonResponse
    {
        $this->authorize('view', $assembly);

        return response()->json([
            'data' => new AssemblyResource($assembly->load(['minutesDocument', 'resolutions', 'creator'])),
        ]);
    }

    public function update(UpdateAssemblyRequest $request, Assembly $assembly): JsonResponse
    {
        $assembly->update($request->validated());

        return response()->json([
            'data' => new AssemblyResource($assembly->fresh(['minutesDocument', 'resolutions', 'creator'])),
        ]);
    }

    public function destroy(Request $request, Assembly $assembly): JsonResponse
    {
        $this->authorize('delete', $assembly);

        $assembly->delete();

        return response()->json(null, 204);
    }

    public function storeResolution(StoreAssemblyResolutionRequest $request, Assembly $assembly): JsonResponse
    {
        $resolution = $assembly->resolutions()->create([
            ...$request->validated(),
            'sort_order' => $assembly->resolutions()->count(),
        ]);

        return response()->json(['data' => new AssemblyResolutionResource($resolution)], 201);
    }

    public function destroyResolution(Request $request, AssemblyResolution $resolution): JsonResponse
    {
        $this->authorize('manageResolutions', $resolution->assembly);

        $resolution->delete();

        return response()->json(null, 204);
    }

    public function storeMinutes(StoreAssemblyMinutesRequest $request, Assembly $assembly): JsonResponse
    {
        $category = DocumentCategory::firstOrCreate(
            ['slug' => 'verbali'],
            ['name' => 'Verbali', 'icon' => 'file-text', 'sort_order' => 99]
        );

        $file = $request->file('file');
        $path = $file->storeAs('documents', Str::uuid().'.'.$file->getClientOriginalExtension(), 'local');

        $document = $assembly->condominium->documents()->create([
            'document_category_id' => $category->id,
            'uploaded_by' => $request->user()->id,
            'title' => "Verbale — {$assembly->title}",
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'visibility' => 'all',
            'published_at' => now(),
        ]);

        $assembly->update(['minutes_document_id' => $document->id]);

        return response()->json([
            'data' => new AssemblyResource($assembly->fresh(['minutesDocument', 'resolutions', 'creator'])),
        ]);
    }
}
