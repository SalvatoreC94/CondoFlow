<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Intervention\StoreInterventionRequest;
use App\Http\Requests\Intervention\UpdateInterventionRequest;
use App\Http\Resources\InterventionResource;
use App\Models\Intervention;
use App\Models\Ticket;
use App\Notifications\InterventionCompleted;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterventionController extends Controller
{
    public function store(StoreInterventionRequest $request, Ticket $ticket): JsonResponse
    {
        $intervention = $ticket->interventions()->create($request->validated());

        return response()->json(['data' => new InterventionResource($intervention->load(['supplier', 'caretaker']))], 201);
    }

    public function update(UpdateInterventionRequest $request, Intervention $intervention): JsonResponse
    {
        $wasCompleted = $intervention->completed_at !== null;

        $intervention->update($request->validated());

        if (! $wasCompleted && $intervention->completed_at) {
            $intervention->ticket->reporter?->notify(new InterventionCompleted($intervention));
        }

        return response()->json(['data' => new InterventionResource($intervention->fresh(['supplier', 'caretaker']))]);
    }

    public function destroy(Request $request, Intervention $intervention): JsonResponse
    {
        $this->authorize('manageInterventions', $intervention->ticket);

        $intervention->delete();

        return response()->json(null, 204);
    }
}
