<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Minishlink\WebPush\WebPush;
use NotificationChannels\WebPush\WebPushChannel;

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

        // Override the package's own binding to pass Laravel's logger: without
        // one, the WebPush client falls back to trigger_error() for its "you
        // should install ext-gmp/ext-bcmath" performance notice, which on a
        // server missing both (common — they're optional) crashes any request
        // that sends a webpush notification once Laravel's error handler
        // converts that notice into a thrown ErrorException. A real logger
        // routes it to the log instead.
        $this->app->when(WebPushChannel::class)
            ->needs(WebPush::class)
            ->give(fn () => (new WebPush(
                auth: $this->vapidAuth(),
                logger: $this->app->make('log'),
            ))->setReuseVAPIDHeaders(true));
    }

    /**
     * @return array<string, mixed>
     */
    private function vapidAuth(): array
    {
        $publicKey = config('webpush.vapid.public_key');
        $privateKey = config('webpush.vapid.private_key');

        if (empty($publicKey) || empty($privateKey)) {
            return [];
        }

        return [
            'VAPID' => [
                'subject' => config('webpush.vapid.subject') ?: url('/'),
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ];
    }
}
