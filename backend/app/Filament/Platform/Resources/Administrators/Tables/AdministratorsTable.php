<?php

namespace App\Filament\Platform\Resources\Administrators\Tables;

use App\Enums\SubscriptionStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AdministratorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('phone')
                    ->label('Cellulare')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('administered_condominiums_count')
                    ->label('Condomini')
                    ->counts('administeredCondominiums')
                    ->sortable(),
                TextColumn::make('subscription_status')
                    ->label('Abbonamento')
                    ->badge()
                    ->formatStateUsing(fn (?SubscriptionStatus $state) => $state?->label())
                    ->color(fn (?SubscriptionStatus $state) => $state?->color() ?? 'gray'),
                TextColumn::make('subscription_plan')
                    ->label('Piano')
                    ->placeholder('—'),
                TextColumn::make('subscription_ends_at')
                    ->label('Scadenza')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Registrato il')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('subscription_status')
                    ->label('Stato abbonamento')
                    ->options(collect(SubscriptionStatus::cases())
                        ->mapWithKeys(fn (SubscriptionStatus $status) => [$status->value => $status->label()])),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
