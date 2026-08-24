<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = Supplier::where('administrator_id', $request->user()->id)
            ->with(['condominiums', 'contacts'])
            ->when($request->filled('condominium_id'), fn ($q) => $q->whereHas('condominiums', fn ($c) => $c->where('condominiums.id', $request->integer('condominium_id'))))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return SupplierResource::collection($suppliers);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::create([
            ...$request->safe()->except('condominium_ids'),
            'administrator_id' => $request->user()->id,
        ]);

        if ($request->filled('condominium_ids')) {
            $supplier->condominiums()->sync($request->validated('condominium_ids'));
        }

        return response()->json(['data' => new SupplierResource($supplier->load(['condominiums', 'contacts']))], 201);
    }

    public function show(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        return response()->json(['data' => new SupplierResource($supplier->load(['condominiums', 'contacts']))]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $supplier->update($request->safe()->except('condominium_ids'));

        if ($request->has('condominium_ids')) {
            $supplier->condominiums()->sync($request->validated('condominium_ids', []));
        }

        return response()->json(['data' => new SupplierResource($supplier->fresh(['condominiums', 'contacts']))]);
    }

    public function destroy(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);

        $supplier->delete();

        return response()->json(null, 204);
    }
}
