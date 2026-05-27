<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->columns(2)
                    ->components([
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('avatar')
                            ->label('Avatar URL')
                            ->url()
                            ->columnSpanFull(),
                    ]),

                Section::make('Access')
                    ->columns(2)
                    ->components([
                        Select::make('roles')
                            ->label('Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),

                        Select::make('agency_id')
                            ->label('Agency')
                            ->relationship('agency', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false),

                        Toggle::make('is_active')
                            ->default(true),

                        DateTimePicker::make('approved_at')
                            ->label('Approved at')
                            ->helperText('Setting this date marks the user as approved.'),
                    ]),

                Section::make('Password')
                    ->columns(1)
                    ->components([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->helperText('Leave empty when editing to keep the current password.'),
                    ]),
            ]);
    }
}
