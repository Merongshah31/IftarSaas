<?php

namespace Database\Factories;

use App\Models\Participant;
use App\Models\IftarDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Participant>
 */
class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $checkinStatus = fake()->randomElement([
            'PENDING', 'PENDING', 'PENDING', 'PENDING', 'PENDING', 'PENDING', 'PENDING',
            'CHECKED_IN', 'CHECKED_IN',
            'NO_SHOW'
        ]);

        $isCancelled = fake()->boolean(10);

        return [
            'iftar_day_id' => IftarDay::factory(),
            'masjid_id' => function (array $attributes) {
                return IftarDay::withoutGlobalScopes()->findOrFail($attributes['iftar_day_id'])->masjid_id;
            },
            'nama' => fake()->name(),  // ✅ Fixed from 'name'
            'no_telefon' => '01' . fake()->randomElement(['2', '3', '7', '9']) . '-' . fake()->numerify('#######'),  // ✅ Fixed from 'phone'
            'bil_pax' => fake()->numberBetween(1, 4),
            'checkin_status' => $checkinStatus,
            'reminder_sent' => fake()->boolean(30),
            'is_cancelled' => $isCancelled,
            'cancelled_at' => $isCancelled ? fake()->dateTimeBetween('-7 days', 'now') : null,
            'cancellation_count' => $isCancelled ? fake()->numberBetween(1, 3) : 0,
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }
}
