<?php

namespace App\Filament\Platform\Resources\TicketCategories\Pages;

use App\Filament\Platform\Resources\TicketCategories\TicketCategoryResource;
use App\Models\PlatformAuditLog;
use Filament\Resources\Pages\CreateRecord;

class CreateTicketCategory extends CreateRecord
{
    protected static string $resource = TicketCategoryResource::class;

    protected function afterCreate(): void
    {
        PlatformAuditLog::logCreate($this->record);
    }
}
