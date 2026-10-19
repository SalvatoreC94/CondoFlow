<?php

namespace App\Providers;

use App\Models\PlatformAuditLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $frontendUrl = rtrim(config('app.frontend_url'), '/');

            return "{$frontendUrl}/reimposta-password?token={$token}&email=".urlencode($notifiable->getEmailForPasswordReset());
        });

        // Every login/logout on the platform-operator panel is worth a
        // record on its own audit trail (who accessed customer data, and
        // when) — not just the create/update/delete actions taken once
        // inside it. Scoped to the `platform` guard only: tenant-side
        // logins already have their own flow and aren't relevant here.
        Event::listen(Login::class, function (Login $event): void {
            if ($event->guard === 'platform') {
                PlatformAuditLog::record('login', actorId: $event->user->getAuthIdentifier());
            }
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->guard === 'platform' && $event->user) {
                PlatformAuditLog::record('logout', actorId: $event->user->getAuthIdentifier());
            }
        });
    }
}
