<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactEvent;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactEvent>
 */
class ContactEventFactory extends Factory
{
    protected $model = ContactEvent::class;

    public function definition(): array
    {
        $contact = Contact::factory()->create();
        $note = Note::factory()->create();

        return [
            'agency_id' => $contact->agency_id,
            'contact_id' => $contact->id,
            'user_id' => User::factory(),
            'eventable_type' => Note::class,
            'eventable_id' => $note->id,
            'occurred_at' => now(),
        ];
    }
}
