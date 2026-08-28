<?php

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Platform\Resources\Administrators\Pages\CreateAdministrator;
use App\Filament\Platform\Resources\Administrators\Pages\EditAdministrator;
use App\Filament\Platform\Resources\Administrators\Pages\ListAdministrators;
use App\Models\Administrator;
use App\Models\PlatformUser;
use App\Models\User;
use App\Notifications\UserInvited;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->platformUser = PlatformUser::factory()->create();
    $this->actingAs($this->platformUser, 'platform');
});

it('lists only administrators', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $caretaker = User::factory()->create(['role' => UserRole::Caretaker]);

    Livewire::test(ListAdministrators::class)
        ->assertCanSeeTableRecords([$administrator])
        ->assertCanNotSeeTableRecords([$caretaker]);
});

it('creates an administrator and sends an invitation notification', function () {
    Notification::fake();

    Livewire::test(CreateAdministrator::class)
        ->fillForm([
            'name' => 'Nuovo Amministratore',
            'email' => 'nuovo@example.com',
            'subscription_status' => SubscriptionStatus::Trial->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $administrator = User::where('email', 'nuovo@example.com')->firstOrFail();

    expect($administrator->role)->toBe(UserRole::Administrator)
        ->and($administrator->status)->toBe(UserStatus::Invited)
        ->and($administrator->invitation_token)->not->toBeNull();

    Notification::assertSentTo(
        Administrator::withoutGlobalScopes()->findOrFail($administrator->id),
        UserInvited::class,
    );
});

it('updates subscription fields on an administrator', function () {
    $administrator = User::factory()->create([
        'role' => UserRole::Administrator,
        'subscription_status' => SubscriptionStatus::Trial,
    ]);

    Livewire::test(EditAdministrator::class, ['record' => $administrator->getRouteKey()])
        ->fillForm([
            'subscription_status' => SubscriptionStatus::Active->value,
            'subscription_plan' => 'Standard',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($administrator->refresh()->subscription_status)->toBe(SubscriptionStatus::Active)
        ->and($administrator->subscription_plan)->toBe('Standard');
});
