<?php

namespace App\Filament\Resources\Agencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AgencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $state, callable $set, ?string $old, $get) {
                                // Auto-suggest slug from name only if slug is empty
                                if (blank($get('slug'))) {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Used in agency-scoped login URLs (e.g. /agencies/{slug}/login).'),

                        Toggle::make('is_active')
                            ->default(true)
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Inactive agencies remain in the database but cannot operate.'),
                    ]),
            ]);
    }
}
