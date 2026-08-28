<?php

namespace App\Filament\Platform\Resources\DocumentCategories\Pages;

use App\Filament\Platform\Resources\DocumentCategories\DocumentCategoryResource;
use App\Models\PlatformAuditLog;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentCategory extends CreateRecord
{
    protected static string $resource = DocumentCategoryResource::class;

    protected function afterCreate(): void
    {
        PlatformAuditLog::logCreate($this->record);
    }
}
