<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use App\Models\ContactEvent;
use App\Models\Note;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Validate;

class ViewContact extends ViewRecord
{
    protected static string $resource = ContactResource::class;

    /**
     * Custom Blade view that lays out the contact card on the left and the
     * note input + timeline on the right.
     */
    protected string $view = 'filament.resources.contacts.pages.view-contact';

    #[Validate('required|string|min:1|max:5000')]
    public string $noteBody = '';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    /**
     * Persist a new Note plus its corresponding ContactEvent on the timeline.
     * Clears the textarea and lets Livewire re-render the timeline.
     */
    public function submitNote(): void
    {
        $this->validateOnly('noteBody');

        $note = Note::create([
            'user_id' => auth()->id(),
            'body' => trim($this->noteBody),
        ]);

        $this->record->events()->create([
            'agency_id' => $this->record->agency_id,
            'user_id' => auth()->id(),
            'eventable_type' => Note::class,
            'eventable_id' => $note->id,
            'occurred_at' => now(),
        ]);

        $this->reset('noteBody');

        Notification::make()
            ->title('Note added')
            ->success()
            ->send();
    }

    /**
     * @return Collection<int, ContactEvent>
     */
    public function getEventsProperty(): Collection
    {
        return $this->record->events()
            ->with(['user', 'eventable'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('created_at')
            ->get();
    }
}
