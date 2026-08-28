<?php

namespace App\Filament\Platform\Resources\Administrators\Pages;

use App\Filament\Platform\Resources\Administrators\AdministratorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdministrators extends ListRecords
{
    protected static string $resource = AdministratorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
