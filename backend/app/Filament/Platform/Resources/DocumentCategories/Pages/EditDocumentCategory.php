<?php

namespace App\Filament\Platform\Resources\DocumentCategories\Pages;

use App\Filament\Platform\Resources\DocumentCategories\DocumentCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumentCategory extends EditRecord
{
    protected static string $resource = DocumentCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
