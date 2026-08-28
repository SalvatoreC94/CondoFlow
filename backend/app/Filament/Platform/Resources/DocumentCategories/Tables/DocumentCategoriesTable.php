<?php

namespace App\Filament\Platform\Resources\DocumentCategories\Tables;

use App\Models\PlatformAuditLog;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class DocumentCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Ordine')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('icon')
                    ->label('Icona'),
                TextColumn::make('documents_count')
                    ->label('Documenti')
                    ->counts('documents'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->after(fn (Collection $records) => $records->each(fn ($record) => PlatformAuditLog::logDelete($record))),
                ]),
            ]);
    }
}
