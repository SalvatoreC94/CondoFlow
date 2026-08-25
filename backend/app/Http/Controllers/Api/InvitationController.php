<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Http\Requests\InviteUserRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\Condominium;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\UserInvited;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationController extends Controller
{
    public function store(InviteUserRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'role' => UserRole::from($data['role']),
            'status' => UserStatus::Invited,
            'invited_by' => $request->user()->id,
            'invitation_token' => Str::random(48),
            'invitation_expires_at' => now()->addDays(7),
        ]);

        if ($data['role'] === 'condomino') {
            $unit = Unit::where('condominium_id', $condominium->id)->findOrFail($data['unit_id']);
            $unit->users()->attach($user->id, [
                'relationship' => $data['relationship'],
                'is_primary' => true,
            ]);
        } else {
            $condominium->caretakers()->attach($user->id);
        }

        AuditLog::record('user.invited', $user, [], ['role' => $data['role']], $condominium->id);

        $response = ['data' => new UserResource($user)];

        if ($user->email) {
            $user->notify(new UserInvited($user->invitation_token, $condominium));
        } else {
            // No email to deliver the invite to: hand the link back to the
            // administrator so they can share it themselves (SMS, WhatsApp…).
            $response['invitation_url'] = rtrim(config('app.frontend_url'), '/')."/accetta-invito/{$user->invitation_token}";
        }

        return response()->json($response, 201);
    }

    public function show(string $token): JsonResponse
    {
        $user = User::where('invitation_token', $token)
            ->where('status', UserStatus::Invited)
            ->where('invitation_expires_at', '>', now())
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'token' => ['Invito non valido o scaduto.'],
            ]);
        }

        return response()->json([
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role->value,
            ],
        ]);
    }

    public function accept(string $token, AcceptInvitationRequest $request): JsonResponse
    {
        $user = User::where('invitation_token', $token)
            ->where('status', UserStatus::Invited)
            ->where('invitation_expires_at', '>', now())
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'token' => ['Invito non valido o scaduto.'],
            ]);
        }

        $user->forceFill([
            'password' => $request->validated('password'),
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
            'invitation_token' => null,
            'invitation_expires_at' => null,
            'invitation_accepted_at' => now(),
        ])->save();

        AuditLog::record('user.invitation_accepted', $user);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['data' => new UserResource($user)]);
    }
}
