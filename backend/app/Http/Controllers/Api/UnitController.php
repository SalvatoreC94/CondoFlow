<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Unit\AttachResidentRequest;
use App\Http\Requests\Unit\StoreUnitRequest;
use App\Http\Requests\Unit\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Condominium;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UnitController extends Controller
{
    public function index(Request $request, Condominium $condominium): AnonymousResourceCollection
    {
        $this->authorize('view', $condominium);

        $units = $condominium->units()
            ->with(['building', 'users'])
            ->when($request->filled('building_id'), fn ($q) => $q->where('building_id', $request->integer('building_id')))
            ->when($request->filled('search'), fn ($q) => $q->where('code', 'like', '%'.$request->string('search').'%'))
            // Group by floor (lowest floor_order first — an administrator-set
            // numeric key, e.g. 0 for ground floor, negative for basements —
            // decoupled from however "floor" is labeled, since that label can
            // be in any language/convention: "T", "PT", "Rez-de-chaussée"…)
            // and, within a floor, by building so units line up by column
            // instead of sorting "code" as plain text.
            ->orderByRaw('floor_order is null')
            ->orderBy('floor_order')
            ->orderBy('building_id')
            ->orderBy('code')
            ->paginate($request->integer('per_page', 50));

        return UnitResource::collection($units);
    }

    public function store(StoreUnitRequest $request, Condominium $condominium): JsonResponse
    {
        $unit = $condominium->units()->create($request->validated());

        return response()->json(['data' => new UnitResource($unit)], 201);
    }

    public function show(Request $request, Unit $unit): JsonResponse
    {
        $this->authorize('view', $unit);

        $unit->load(['building', 'users']);

        return response()->json(['data' => new UnitResource($unit)]);
    }

    public function update(UpdateUnitRequest $request, Unit $unit): JsonResponse
    {
        $unit->update($request->validated());

        return response()->json(['data' => new UnitResource($unit->fresh(['building', 'users']))]);
    }

    public function destroy(Request $request, Unit $unit): JsonResponse
    {
        $this->authorize('delete', $unit);

        $unit->delete();

        return response()->json(null, 204);
    }

    public function attachResident(AttachResidentRequest $request, Unit $unit): JsonResponse
    {
        $data = $request->validated();

        $unit->users()->syncWithoutDetaching([
            $data['user_id'] => [
                'relationship' => $data['relationship'],
                'is_primary' => $data['is_primary'] ?? false,
            ],
        ]);

        return response()->json(['data' => new UnitResource($unit->fresh(['building', 'users']))]);
    }

    public function detachResident(Request $request, Unit $unit, User $user): JsonResponse
    {
        $this->authorize('manageResidents', $unit);

        $unit->users()->detach($user->id);

        return response()->json(null, 204);
    }
}
