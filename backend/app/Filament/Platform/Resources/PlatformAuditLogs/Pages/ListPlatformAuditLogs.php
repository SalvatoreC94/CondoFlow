<?php

namespace App\Filament\Platform\Resources\PlatformAuditLogs\Pages;

use App\Filament\Platform\Resources\PlatformAuditLogs\PlatformAuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListPlatformAuditLogs extends ListRecords
{
    protected static string $resource = PlatformAuditLogResource::class;
}
