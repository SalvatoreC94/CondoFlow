<?php

use App\Filament\Platform\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Platform\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Filament\Platform\Resources\Condominia\Pages\ListCondominia;
use App\Filament\Platform\Resources\Condominia\Pages\ViewCondominium;
use App\Models\AuditLog;
use App\Models\Condominium;
use App\Models\PlatformUser;
use Livewire\Livewire;

beforeEach(function () {
    $this->platformUser = PlatformUser::factory()->create();
    $this->actingAs($this->platformUser, 'platform');
});

it('lists condominiums even though CondominiumPolicy is typed to the web guards User model', function () {
    $condominium = Condominium::factory()->create();

    Livewire::test(ListCondominia::class)
        ->assertCanSeeTableRecords([$condominium]);
});

it('views a single condominium', function () {
    $condominium = Condominium::factory()->create();

    Livewire::test(ViewCondominium::class, ['record' => $condominium->getRouteKey()])
        ->assertOk();
});

it('lists audit log entries', function () {
    $entry = AuditLog::create([
        'action' => 'user.invited',
        'created_at' => now(),
    ]);

    Livewire::test(ListAuditLogs::class)
        ->assertCanSeeTableRecords([$entry]);
});

it('views a single audit log entry', function () {
    $entry = AuditLog::create([
        'action' => 'user.invited',
        'created_at' => now(),
    ]);

    Livewire::test(ViewAuditLog::class, ['record' => $entry->getRouteKey()])
        ->assertOk();
});
