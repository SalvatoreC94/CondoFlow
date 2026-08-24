<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Condominium;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CondominiumUserController extends Controller
{
    public function index(Request $request, Condominium $condominium): JsonResponse
    {
        $this->authorize('view', $condominium);

        $residents = User::whereHas('units', fn ($q) => $q->where('units.condominium_id', $condominium->id))
            ->with(['units' => fn ($q) => $q->where('units.condominium_id', $condominium->id)])
            ->get();

        $caretakers = $condominium->caretakers()->get();

        return response()->json([
            'data' => [
                'residents' => UserResource::collection($residents),
                'caretakers' => UserResource::collection($caretakers),
            ],
        ]);
    }

    public function detachCaretaker(Request $request, Condominium $condominium, User $user): JsonResponse
    {
        $this->authorize('manageStaff', $condominium);

        $condominium->caretakers()->detach($user->id);

        return response()->json(null, 204);
    }
}
