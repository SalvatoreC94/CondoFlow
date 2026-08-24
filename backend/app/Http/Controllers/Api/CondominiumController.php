<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Condominium\StoreCondominiumRequest;
use App\Http\Requests\Condominium\UpdateCondominiumRequest;
use App\Http\Resources\CondominiumResource;
use App\Models\AuditLog;
use App\Models\Condominium;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
