<?php

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Filament\Platform\Resources\Administrators\Pages\CreateAdministrator;
use App\Filament\Platform\Resources\Administrators\Pages\EditAdministrator;
use App\Filament\Platform\Resources\PlatformAuditLogs\Pages\ListPlatformAuditLogs;
use App\Filament\Platform\Resources\PlatformAuditLogs\Pages\ViewPlatformAuditLog;
use App\Filament\Platform\Resources\TicketCategories\Pages\EditTicketCategory;
use App\Models\PlatformAuditLog;
use App\Models\PlatformUser;
use App\Models\TicketCategory;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Livewire\Livewire;

beforeEach(function () {
    $this->platformUser = PlatformUser::factory()->create(['password' => 'password']);
});

it('logs a login and a logout on the platform guard', function () {
    Livewire::test(Login::class)
        ->fillForm(['email' => $this->platformUser->email, 'password' => 'password'])
        ->call('authenticate');

    expect(PlatformAuditLog::where('action', 'login')->where('platform_user_id', $this->platformUser->id)->exists())->toBeTrue();

    $this->post('/platform/logout');

    expect(PlatformAuditLog::where('action', 'logout')->where('platform_user_id', $this->platformUser->id)->exists())->toBeTrue();
});

it('logs administrator creation with the acting operator attributed', function () {
    $this->actingAs($this->platformUser, 'platform');

    Livewire::test(CreateAdministrator::class)
        ->fillForm([
            'name' => 'Nuovo Amministratore',
            'email' => 'nuovo-audit@example.com',
            'subscription_status' => SubscriptionStatus::Trial->value,
        ])
        ->call('create');

    $entry = PlatformAuditLog::where('action', 'administrator.created')->latest('id')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->platform_user_id)->toBe($this->platformUser->id)
        ->and($entry->new_values['email'])->toBe('nuovo-audit@example.com')
        ->and($entry->new_values)->not->toHaveKey('invitation_token');
});

it('logs only the changed fields on an administrator update, with before/after values', function () {
    $this->actingAs($this->platformUser, 'platform');

    $administrator = User::factory()->create([
        'role' => UserRole::Administrator,
        'subscription_status' => SubscriptionStatus::Trial,
        'subscription_plan' => null,
    ]);

    Livewire::test(EditAdministrator::class, ['record' => $administrator->getRouteKey()])
        ->fillForm(['subscription_status' => SubscriptionStatus::Active->value, 'subscription_plan' => 'Standard'])
        ->call('save');

    $entry = PlatformAuditLog::where('action', 'administrator.updated')->where('auditable_id', $administrator->id)->latest('id')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->old_values)->toMatchArray(['subscription_status' => 'trial', 'subscription_plan' => null])
        ->and($entry->new_values)->toMatchArray(['subscription_status' => 'active', 'subscription_plan' => 'Standard'])
        ->and($entry->old_values)->not->toHaveKey('name');
});

it('does not log an update when the form is saved without any actual change', function () {
    $this->actingAs($this->platformUser, 'platform');

    $category = TicketCategory::factory()->create();

    Livewire::test(EditTicketCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm(['name' => $category->name])
        ->call('save');

    expect(PlatformAuditLog::where('action', 'ticket_category.updated')->where('auditable_id', $category->id)->exists())->toBeFalse();
});

it('lets a platform operator view the platform audit log itself', function () {
    $this->actingAs($this->platformUser, 'platform');

    $entry = PlatformAuditLog::record('login', actorId: $this->platformUser->id);

    Livewire::test(ListPlatformAuditLogs::class)->assertCanSeeTableRecords([$entry]);
    Livewire::test(ViewPlatformAuditLog::class, ['record' => $entry->getRouteKey()])->assertOk();
});
