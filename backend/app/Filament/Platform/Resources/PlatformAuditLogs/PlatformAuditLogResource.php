<?php

namespace App\Filament\Platform\Resources\PlatformAuditLogs;

use App\Filament\Platform\Resources\PlatformAuditLogs\Pages\ListPlatformAuditLogs;
use App\Filament\Platform\Resources\PlatformAuditLogs\Pages\ViewPlatformAuditLog;
use App\Filament\Platform\Resources\PlatformAuditLogs\Schemas\PlatformAuditLogInfolist;
use App\Filament\Platform\Resources\PlatformAuditLogs\Tables\PlatformAuditLogsTable;
use App\Models\PlatformAuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Read-only trail of every action taken by a platform operator (create/
 * update/delete on the panel's own resources, plus login/logout) — the
 * "who accessed/changed customer data, and when" answer for GDPR-style
 * accountability requests. Separate from AuditLogResource, which covers
 * the tenant-facing `web` guard.
 */
class PlatformAuditLogResource extends Resource
{
    protected static ?string $model = PlatformAuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Log operatori';

    public static function infolist(Schema $schema): Schema
    {
        return PlatformAuditLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlatformAuditLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformAuditLogs::route('/'),
            'view' => ViewPlatformAuditLog::route('/{record}'),
        ];
    }
}
