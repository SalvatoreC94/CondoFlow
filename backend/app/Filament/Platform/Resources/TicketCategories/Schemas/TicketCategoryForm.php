<?php

namespace App\Filament\Platform\Resources\TicketCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TicketCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                TextInput::make('icon')
                    ->label('Icona')
                    ->helperText('Nome icona Lucide (es. wrench, lightbulb, sparkles...).')
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->label('Ordine')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Attiva')
                    ->default(true),
            ]);
    }
}
