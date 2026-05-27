<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Enums\LeadStatus;
use App\Models\Contact;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('agency_id')
                    ->label('Agency')
                    ->relationship('agency', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false),

                Section::make('Source')
                    ->description('Lead origin — typically populated by the inbound webhook.')
                    ->columns(2)
                    ->components([
                        TextInput::make('platform')
                            ->required()
                            ->disabledOn('edit'),
                        TextInput::make('platform_lead_id')
                            ->label('Platform lead ID')
                            ->required()
                            ->disabledOn('edit'),
                        TextInput::make('form_id')->disabledOn('edit'),
                        TextInput::make('ad_id')->disabledOn('edit'),
                        TextInput::make('campaign_id')->disabledOn('edit'),
                        DateTimePicker::make('captured_at')
                            ->required()
                            ->disabledOn('edit'),
                    ]),

                Section::make('Payload')
                    ->description('Snapshot of the raw payload received from the source.')
                    ->components([
                        KeyValue::make('payload')
                            ->editableKeys(false)
                            ->editableValues(false)
                            ->addable(false)
                            ->deletable(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('Processing')
                    ->columns(2)
                    ->components([
                        Select::make('status')
                            ->options(LeadStatus::class)
                            ->default(LeadStatus::Pending)
                            ->disabled()
                            ->dehydrated(false),
                        DateTimePicker::make('processed_at')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Section::make('Linked contact')
                    ->components([
                        Select::make('contact_id')
                            ->label('Contact')
                            ->options(fn () => Contact::query()
                                ->orderBy('last_name')
                                ->get()
                                ->mapWithKeys(fn (Contact $c) => [
                                    $c->id => trim($c->first_name.' '.$c->last_name) ?: ('Contact #'.$c->id),
                                ]))
                            ->searchable()
                            ->placeholder('Not linked yet')
                            ->helperText('Link to an existing contact, or leave empty to let the processing job resolve it.'),
                    ]),
            ]);
    }
}
