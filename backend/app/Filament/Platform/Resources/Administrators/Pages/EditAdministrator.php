<?php

namespace App\Filament\Platform\Resources\Administrators\Pages;

use App\Filament\Platform\Resources\Administrators\AdministratorResource;
use App\Models\PlatformAuditLog;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditAdministrator extends EditRecord
{
    protected static string $resource = AdministratorResource::class;

    protected array $auditOriginal = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->after(fn ($record) => PlatformAuditLog::logDelete($record)),
            ForceDeleteAction::make()->after(fn ($record) => PlatformAuditLog::logForceDelete($record)),
            RestoreAction::make()->after(fn ($record) => PlatformAuditLog::logRestore($record)),
        ];
    }

    protected function beforeSave(): void
    {
        $this->auditOriginal = $this->record->getOriginal();
    }

    protected function afterSave(): void
    {
        $changedKeys = array_keys($this->record->getChanges());

        if ($changedKeys === [] || $changedKeys === ['updated_at']) {
            return;
        }

        PlatformAuditLog::logUpdate($this->record, $this->auditOriginal, array_diff($changedKeys, ['updated_at']));
    }
}
