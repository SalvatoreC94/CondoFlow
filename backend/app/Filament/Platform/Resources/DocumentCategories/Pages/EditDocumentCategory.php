<?php

namespace App\Filament\Platform\Resources\DocumentCategories\Pages;

use App\Filament\Platform\Resources\DocumentCategories\DocumentCategoryResource;
use App\Models\PlatformAuditLog;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumentCategory extends EditRecord
{
    protected static string $resource = DocumentCategoryResource::class;

    protected array $auditOriginal = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->after(fn ($record) => PlatformAuditLog::logDelete($record)),
        ];
    }

    protected function beforeSave(): void
    {
        $this->auditOriginal = $this->record->getOriginal();
    }

    protected function afterSave(): void
    {
        $changedKeys = array_diff(array_keys($this->record->getChanges()), ['updated_at']);

        if ($changedKeys === []) {
            return;
        }

        PlatformAuditLog::logUpdate($this->record, $this->auditOriginal, $changedKeys);
    }
}
