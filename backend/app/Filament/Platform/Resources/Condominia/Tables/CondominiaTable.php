<?php

namespace App\Filament\Platform\Resources\Condominia\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CondominiaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('administrator.name')
                    ->label('Amministratore')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->label('Città')
                    ->searchable(),
                TextColumn::make('total_units')
                    ->label('Unità'),
                TextColumn::make('units_count')
                    ->label('Unità censite')
                    ->counts('units'),
                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
