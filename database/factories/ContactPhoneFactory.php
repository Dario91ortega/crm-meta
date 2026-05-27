<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactPhone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactPhone>
 */
class ContactPhoneFactory extends Factory
{
    protected $model = ContactPhone::class;

    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'phone' => (int) fake()->numerify('5#############'),
            'label' => fake()->randomElement(['mobile', 'home', 'work']),
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }
}
