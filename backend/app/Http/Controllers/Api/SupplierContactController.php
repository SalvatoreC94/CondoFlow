<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierContactRequest;
use App\Http\Resources\SupplierContactResource;
use App\Models\Supplier;
use App\Models\SupplierContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierContactController extends Controller
{
    public function store(StoreSupplierContactRequest $request, Supplier $supplier): JsonResponse
    {
        $contact = $supplier->contacts()->create($request->validated());

        return response()->json(['data' => new SupplierContactResource($contact)], 201);
    }

    public function destroy(Request $request, Supplier $supplier, SupplierContact $contact): JsonResponse
    {
        $this->authorize('update', $supplier);
        abort_if($contact->supplier_id !== $supplier->id, 404);

        $contact->delete();

        return response()->json(null, 204);
    }
}
