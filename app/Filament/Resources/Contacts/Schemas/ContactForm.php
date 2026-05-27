<?php

namespace App\Filament\Resources\Contacts\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->columns(2)
                    ->components([
                        TextInput::make('first_name')
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->maxLength(255),
                        TextInput::make('avatar')
                            ->label('Avatar URL')
                            ->url()
                            ->columnSpanFull(),
                    ]),

                Section::make('Ownership')
                    ->columns(2)
                    ->components([
                        // Visible only for super-admins (admin role + no agency).
                        // Regular users get their agency_id auto-filled by the
                        // Contact model's creating hook, so they never see this.
                        Select::make('agency_id')
                            ->label('Agency')
                            ->relationship('agency', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false),

                        Select::make('user_id')
                            ->label('Assigned to')
                            ->options(function () {
                                $agencyId = auth()->user()?->agency_id;

                                return User::query()
                                    ->when($agencyId, fn ($q) => $q->where('agency_id', $agencyId))
                                    ->where('is_active', true)
                                    ->orderBy('first_name')
                                    ->pluck('name', 'id');
                            })
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->required(),
                    ]),
            ]);
    }
}
