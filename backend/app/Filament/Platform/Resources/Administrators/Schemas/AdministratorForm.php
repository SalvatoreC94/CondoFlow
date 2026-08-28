<?php

namespace App\Filament\Platform\Resources\Administrators\Schemas;

use App\Enums\SubscriptionStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdministratorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Anagrafica')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome e cognome')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->requiredWithout('phone')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('phone')
                            ->label('Cellulare')
                            ->tel()
                            ->requiredWithout('email')
                            ->maxLength(30)
                            ->unique(ignoreRecord: true),
                    ]),
                Section::make('Abbonamento')
                    ->columns(2)
                    ->schema([
                        Select::make('subscription_status')
                            ->label('Stato abbonamento')
                            ->options(collect(SubscriptionStatus::cases())
                                ->mapWithKeys(fn (SubscriptionStatus $status) => [$status->value => $status->label()]))
                            ->default(SubscriptionStatus::Trial->value)
                            ->required()
                            ->native(false),
                        TextInput::make('subscription_plan')
                            ->label('Piano')
                            ->maxLength(255)
                            ->placeholder('es. Trial, Standard...'),
                        DateTimePicker::make('subscription_ends_at')
                            ->label('Scadenza abbonamento')
                            ->native(false),
                        Textarea::make('subscription_notes')
                            ->label('Note interne')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
