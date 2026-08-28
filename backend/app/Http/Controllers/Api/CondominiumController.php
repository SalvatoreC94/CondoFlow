<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Condominium\StoreCondominiumRequest;
use App\Http\Requests\Condominium\UpdateCondominiumLogoRequest;
use App\Http\Requests\Condominium\UpdateCondominiumRequest;
use App\Http\Resources\CondominiumResource;
use App\Models\AuditLog;
use App\Models\Condominium;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CondominiumController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Condominium::query()->withCount('units');

        $query = match ($user->role) {
            UserRole::Administrator => $query->where('administrator_id', $user->id),
            UserRole::Caretaker => $query->whereHas('caretakers', fn ($q) => $q->where('users.id', $user->id)),
            UserRole::Condomino => $query->whereHas('units.users', fn ($q) => $q->where('users.id', $user->id)),
        };

        return response()->json([
            'data' => CondominiumResource::collection($query->orderBy('name')->get()),
        ]);
    }

    public function store(StoreCondominiumRequest $request): JsonResponse
    {
        $condominium = Condominium::create([
            ...$request->validated(),
            'administrator_id' => $request->user()->id,
        ]);

        AuditLog::record('condominium.created', $condominium, [], $request->validated(), $condominium->id);

        return response()->json(['data' => new CondominiumResource($condominium)], 201);
    }

    public function show(Request $request, Condominium $condominium): JsonResponse
    {
        $this->authorize('view', $condominium);

        $condominium->loadCount('units')->load('administrator');

        return response()->json(['data' => new CondominiumResource($condominium)]);
    }

    public function update(UpdateCondominiumRequest $request, Condominium $condominium): JsonResponse
    {
        $old = $condominium->only(array_keys($request->validated()));
        $condominium->update($request->validated());

        AuditLog::record('condominium.updated', $condominium, $old, $request->validated(), $condominium->id);

        return response()->json(['data' => new CondominiumResource($condominium)]);
    }

    public function destroy(Request $request, Condominium $condominium): JsonResponse
    {
        $this->authorize('delete', $condominium);

        $condominium->delete();

        AuditLog::record('condominium.deleted', $condominium, [], [], $condominium->id);

        return response()->json(null, 204);
    }

    public function logo(Request $request, Condominium $condominium): StreamedResponse|JsonResponse
    {
        $this->authorize('view', $condominium);

        if (! $condominium->logo_path || ! Storage::disk('local')->exists($condominium->logo_path)) {
            return response()->json(['message' => 'Nessun logo impostato.'], 404);
        }

        return Storage::disk('local')->response($condominium->logo_path, null, [
            'Content-Type' => $condominium->logo_mime_type,
        ]);
    }

    public function uploadLogo(UpdateCondominiumLogoRequest $request, Condominium $condominium): JsonResponse
    {
        $previousPath = $condominium->logo_path;

        $file = $request->file('logo');
        $path = $file->storeAs('condominium-logos', Str::uuid().'.'.$file->getClientOriginalExtension(), 'local');

        $condominium->update([
            'logo_path' => $path,
            'logo_mime_type' => $file->getMimeType(),
        ]);

        if ($previousPath) {
            Storage::disk('local')->delete($previousPath);
        }

        AuditLog::record('condominium.logo_updated', $condominium, [], [], $condominium->id);

        return response()->json(['data' => new CondominiumResource($condominium)]);
    }

    public function removeLogo(Request $request, Condominium $condominium): JsonResponse
    {
        $this->authorize('update', $condominium);

        if ($condominium->logo_path) {
            Storage::disk('local')->delete($condominium->logo_path);
        }

        $condominium->update(['logo_path' => null, 'logo_mime_type' => null]);

        AuditLog::record('condominium.logo_removed', $condominium, [], [], $condominium->id);

        return response()->json(['data' => new CondominiumResource($condominium)]);
    }
}
