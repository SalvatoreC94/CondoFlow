<?php

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Filament\Platform\Widgets\PlatformStatsOverview;
use App\Models\Condominium;
use App\Models\PlatformUser;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->platformUser = PlatformUser::factory()->create();
    $this->actingAs($this->platformUser, 'platform');
});

it('renders the platform dashboard', function () {
    $this->get('/platform')->assertOk();
});

it('renders the stats overview widget with platform-wide counts', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator, 'subscription_status' => SubscriptionStatus::Active]);
    Condominium::factory()->for($administrator, 'administrator')->create(['total_units' => 20]);

    Livewire::test(PlatformStatsOverview::class)->assertOk();
});
