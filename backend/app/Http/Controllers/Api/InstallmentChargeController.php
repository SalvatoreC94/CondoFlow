<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Installment\UpdateInstallmentChargeRequest;
use App\Http\Resources\InstallmentChargeResource;
use App\Models\AuditLog;
use App\Models\InstallmentCharge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstallmentChargeController extends Controller
{
    /**
     * The authenticated condomino's own charges, across every unit they
     * belong to — the "Le mie spese" view. Staff calling this simply get an
     * empty list, since they don't have units of their own.
     */
    public function mine(Request $request): JsonResponse
    {
        $unitIds = $request->user()->units()->pluck('units.id');

        $charges = InstallmentCharge::whereIn('unit_id', $unitIds)
            ->with(['unit.condominium', 'installment'])
            ->get()
            ->sortByDesc(fn (InstallmentCharge $charge) => $charge->installment->due_date)
            ->values();

        return response()->json(['data' => InstallmentChargeResource::collection($charges)]);
    }

    public function update(UpdateInstallmentChargeRequest $request, InstallmentCharge $installmentCharge): JsonResponse
    {
        $paid = $request->boolean('paid');

        $installmentCharge->update([
            'paid' => $paid,
            'paid_at' => $paid ? now() : null,
        ]);

        AuditLog::record(
            'installment_charge.updated',
            $installmentCharge,
            [],
            ['paid' => $paid],
            $installmentCharge->unit->condominium_id
        );

        return response()->json(['data' => new InstallmentChargeResource($installmentCharge->fresh(['unit', 'installment']))]);
    }
}
