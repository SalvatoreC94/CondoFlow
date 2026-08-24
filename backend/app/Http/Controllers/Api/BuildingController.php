<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Building\StoreBuildingRequest;
use App\Http\Requests\Building\UpdateBuildingRequest;
use App\Http\Resources\BuildingResource;
use App\Models\Building;
use App\Models\Condominium;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function index(Request $request, Condominium $condominium): JsonResponse
    {
        $this->authorize('view', $condominium);

        $buildings = $condominium->buildings()->withCount('units')->orderBy('name')->get();

        return response()->json(['data' => BuildingResource::collection($buildings)]);
    }

    public function store(StoreBuildingRequest $request, Condominium $condominium): JsonResponse
    {
        $building = $condominium->buildings()->create($request->validated());

        return response()->json(['data' => new BuildingResource($building)], 201);
    }

    public function update(UpdateBuildingRequest $request, Building $building): JsonResponse
    {
        $building->update($request->validated());

        return response()->json(['data' => new BuildingResource($building)]);
    }

    public function destroy(Request $request, Building $building): JsonResponse
    {
        $this->authorize('delete', $building);

        $building->delete();

        return response()->json(null, 204);
    }
}
