<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $identifier = $request->validated('identifier');

        // The identifier can be either an email address or a phone number,
        // so the user is looked up manually (rather than via Auth::attempt,
        // which only knows how to match a single fixed column) and logged
        // in directly once the password checks out.
        $user = User::where('email', $identifier)->orWhere('phone', $identifier)->first();

        if (! $user || $user->status !== UserStatus::Active || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => ['Le credenziali fornite non sono corrette.'],
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        AuditLog::record('auth.login', $user);

        return response()->json([
            'data' => new UserResource($this->loadUserContext($user)),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        AuditLog::record('auth.logout', $request->user());

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Disconnesso.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($this->loadUserContext($request->user())),
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        // Always return the same response regardless of outcome, so the
        // endpoint cannot be used to enumerate registered email addresses.
        Password::sendResetLink($request->only('email'));

        return response()->json(['message' => 'Se l\'indirizzo esiste, riceverai un\'email con le istruzioni.']);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                AuditLog::record('auth.password_reset', $user);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => 'Password aggiornata con successo.']);
    }

    private function loadUserContext(User $user): User
    {
        return match ($user->role->value) {
            'administrator' => $user->load('administeredCondominiums'),
            'caretaker' => $user->load('assignedCondominiums'),
            'condomino' => $user->load('units.condominium', 'units.building'),
            default => $user,
        };
    }
}
