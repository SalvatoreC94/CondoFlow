<?php

namespace App\Filament\Platform\Resources\TicketCategories\Pages;

use App\Filament\Platform\Resources\TicketCategories\TicketCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTicketCategory extends CreateRecord
{
    protected static string $resource = TicketCategoryResource::class;
}
