<?php

use App\Filament\Platform\Resources\DocumentCategories\Pages\CreateDocumentCategory;
use App\Filament\Platform\Resources\DocumentCategories\Pages\EditDocumentCategory;
use App\Filament\Platform\Resources\TicketCategories\Pages\CreateTicketCategory;
use App\Filament\Platform\Resources\TicketCategories\Pages\EditTicketCategory;
use App\Models\DocumentCategory;
use App\Models\PlatformUser;
use App\Models\TicketCategory;
use Livewire\Livewire;

beforeEach(function () {
    $this->platformUser = PlatformUser::factory()->create();
    $this->actingAs($this->platformUser, 'platform');
});

it('creates a ticket category and auto-generates its slug', function () {
    Livewire::test(CreateTicketCategory::class)
        ->fillForm([
            'name' => 'Ascensore',
            'icon' => 'arrow-up-down',
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $category = TicketCategory::where('name', 'Ascensore')->firstOrFail();

    expect($category->slug)->toBe('ascensore');
});

it('updates a ticket category', function () {
    $category = TicketCategory::factory()->create(['is_active' => true]);

    Livewire::test(EditTicketCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->refresh()->is_active)->toBeFalse();
});

it('creates a document category and auto-generates its slug', function () {
    Livewire::test(CreateDocumentCategory::class)
        ->fillForm([
            'name' => 'Bilanci',
            'icon' => 'calculator',
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $category = DocumentCategory::where('name', 'Bilanci')->firstOrFail();

    expect($category->slug)->toBe('bilanci');
});

it('updates a document category', function () {
    $category = DocumentCategory::factory()->create();

    Livewire::test(EditDocumentCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm(['name' => 'Rinominata'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->refresh()->name)->toBe('Rinominata');
});
