<?php

namespace App\Filament\Platform\Resources\DocumentCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DocumentCategoryForm
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
                    ->helperText('Nome icona Lucide (es. book, file-text, shield-check...).')
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->label('Ordine')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }
}
