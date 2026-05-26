<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactEmail>
 */
class ContactEmailFactory extends Factory
{
    protected $model = ContactEmail::class;

    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'email' => fake()->unique()->safeEmail(),
            'label' => fake()->randomElement(['personal', 'work']),
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }
}
