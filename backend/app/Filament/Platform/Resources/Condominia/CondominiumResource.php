<?php

namespace App\Filament\Platform\Resources\Condominia;

use App\Filament\Platform\Resources\Condominia\Pages\ListCondominia;
use App\Filament\Platform\Resources\Condominia\Pages\ViewCondominium;
use App\Filament\Platform\Resources\Condominia\Schemas\CondominiumInfolist;
use App\Filament\Platform\Resources\Condominia\Tables\CondominiaTable;
use App\Models\Condominium;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CondominiumResource extends Resource
{
    protected static ?string $model = Condominium::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    /**
     * Condominium already has a `CondominiumPolicy` written for the app's
     * `web` guard (typed to `App\Models\User`). Laravel silently denies any
     * policy check whose authenticated guard's user doesn't match that type
     * hint, which would lock the platform operator (a `PlatformUser`) out
     * entirely. This panel is read-only and only reachable by an
     * authenticated platform operator, so authorization is intentionally
     * bypassed here — overriding the root of `HasAuthorization` covers
     * every derived check (canViewAny, canView, can('view', ...), etc.)
     * rather than the tenant-facing policy.
     */
    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        return Response::allow();
    }

    public static function infolist(Schema $schema): Schema
    {
        return CondominiumInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CondominiaTable::configure($table);
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
            'index' => ListCondominia::route('/'),
            'view' => ViewCondominium::route('/{record}'),
        ];
    }
}
