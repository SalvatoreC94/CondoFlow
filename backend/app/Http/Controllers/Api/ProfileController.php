<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $old = $user->only(['name', 'email', 'phone']);

        $user->update($request->validated());

        AuditLog::record('user.profile_updated', $user, $old, $request->validated());

        return response()->json(['data' => new UserResource($user)]);
    }

    public function updatePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update(['password' => $request->validated('password')]);

        AuditLog::record('user.password_changed', $user);

        return response()->json(['message' => 'Password aggiornata.']);
    }
}
