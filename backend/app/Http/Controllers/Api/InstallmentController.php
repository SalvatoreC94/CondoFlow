<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Installment\StoreInstallmentRequest;
use App\Http\Resources\InstallmentResource;
use App\Models\AuditLog;
use App\Models\Condominium;
use App\Models\Installment;
use App\Services\InstallmentSplitter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class InstallmentController extends Controller
{
    public function __construct(private readonly InstallmentSplitter $splitter) {}

    public function index(Request $request, Condominium $condominium): AnonymousResourceCollection
    {
        $this->authorize('manageFinances', $condominium);

        $installments = $condominium->installments()
            ->with('charges')
            ->orderByDesc('due_date')
            ->paginate($request->integer('per_page', 20));

        return InstallmentResource::collection($installments);
    }

    public function store(StoreInstallmentRequest $request, Condominium $condominium): JsonResponse
    {
        $installment = $this->splitter->create($condominium, $request->user(), $request->validated());

        AuditLog::record('installment.created', $installment, [], $request->validated(), $condominium->id);

        return response()->json(['data' => new InstallmentResource($installment->load('charges.unit'))], 201);
    }

    public function show(Request $request, Installment $installment): JsonResponse
    {
        $this->authorize('view', $installment);

        return response()->json(['data' => new InstallmentResource($installment->load('charges.unit', 'creator'))]);
    }

    public function destroy(Request $request, Installment $installment): JsonResponse
    {
        $this->authorize('delete', $installment);

        if ($installment->charges()->where('paid', true)->exists()) {
            throw ValidationException::withMessages([
                'installment' => ['Non è possibile eliminare una rata con quote già segnate come pagate.'],
            ]);
        }

        AuditLog::record('installment.deleted', $installment, [], [], $installment->condominium_id);

        $installment->delete();

        return response()->json(null, 204);
    }
}
