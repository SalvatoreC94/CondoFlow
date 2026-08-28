<?php

namespace App\Filament\Platform\Resources\AuditLogs\Pages;

use App\Filament\Platform\Resources\AuditLogs\AuditLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;
}
