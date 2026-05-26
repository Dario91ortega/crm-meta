<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Models\Agency;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'contact_id' => null,
            'platform' => 'meta',
            'platform_lead_id' => (string) fake()->unique()->numerify('##############'),
            'form_id' => (string) fake()->numerify('############'),
            'ad_id' => (string) fake()->numerify('############'),
            'campaign_id' => (string) fake()->numerify('############'),
            'payload' => [
                'email' => fake()->unique()->safeEmail(),
                'phone' => fake()->e164PhoneNumber(),
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
            ],
            'status' => LeadStatus::Pending,
            'captured_at' => now(),
            'processed_at' => null,
        ];
    }

    public function processed(): static
    {
        return $this->state(fn () => [
            'status' => LeadStatus::Processed,
            'processed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => LeadStatus::Failed,
            'processed_at' => now(),
        ]);
    }
}
