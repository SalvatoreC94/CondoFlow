<?php

use App\Enums\UserRole;
use App\Models\PlatformUser;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Livewire\Livewire;

it('redirects a guest to the platform login instead of the dashboard', function () {
    $this->get('/platform')->assertRedirect('/platform/login');
});

it('never lets a tenant administrator authenticated on the web guard into the platform panel', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);

    $this->actingAs($administrator, 'web')
        ->get('/platform')
        ->assertRedirect('/platform/login');

    $this->actingAs($administrator, 'web')
        ->get('/platform/administrators')
        ->assertRedirect('/platform/login');
});

it('never lets a condomino or caretaker authenticated on the web guard into the platform panel', function () {
    $condomino = User::factory()->create(['role' => UserRole::Condomino]);

    $this->actingAs($condomino, 'web')
        ->get('/platform')
        ->assertRedirect('/platform/login');
});

it('never lets a platform operator authenticate against the tenant-facing api guard', function () {
    $platformUser = PlatformUser::factory()->create();

    $this->actingAs($platformUser, 'platform')
        ->getJson('/api/me')
        ->assertUnauthorized();
});

it('never lets a tenant user log into the platform panel with their web credentials', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator, 'password' => 'correct-password']);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $administrator->email,
            'password' => 'correct-password',
        ])
        ->call('authenticate');

    $this->assertGuest('platform');
});
