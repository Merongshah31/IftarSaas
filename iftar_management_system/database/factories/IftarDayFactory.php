<?php

namespace Database\Factories;

use App\Models\IftarDay;
use App\Models\Masjid;
use Illuminate\Database\Eloquent\Factories\Factory;

class IftarDayFactory extends Factory
{
    protected $model = IftarDay::class;

    public function definition(): array
    {
        return [
            'masjid_id' => Masjid::factory(),
            'tarikh' => fake()->dateTimeBetween('2026-03-01', '2026-04-30'),
            'kapasiti_max' => fake()->randomElement([50, 100, 150, 200, 300]),  
            'jumlah_daftar' => 0,
            'status' => fake()->randomElement(['open', 'open', 'open', 'full', 'closed']),
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }
}
