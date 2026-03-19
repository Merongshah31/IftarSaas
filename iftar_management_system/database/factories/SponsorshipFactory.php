<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Sponsorship;
use App\Models\IftarDay;
use App\Models\Masjid;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sponsorship>
 */
class SponsorshipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Sponsorship::class;

    public function definition(): array
    {

        //company 
        $sponsors = [
            'Syarikat Berjaya Sdn Bhd',
            'Encik Ahmad bin Abdullah',
            'Puan Siti Nurhaliza',
            'Kedai Makan Pak Ali',
            'Abdullah & Associates',
            'Derma Harian Metro',
            'Yayasan Kebajikan',
        ];

        //event types
        $eventType = fake()->randomElement(['IFTAR', 'MOREH']);

        $coverage = fake()->randomElement([
            'FULL', 'FULL', 'FULL',
            'PARTIAL', 'PARTIAL',
            'GENERAL'
            ]);
        
        $jumlahTajaan = match($coverage) {
            'FULL' => fake()->randomFloat(2, 3000, 5000),
            'PARTIAL' => fake()->randomFloat(2, 500, 2000),
            'GENERAL' => fake()->randomFloat(2, 100, 499),
        };

        return [
            'iftar_day_id' => IftarDay::factory(),
            'masjid_id' => Masjid::factory(),
            'nama_sponsor' => fake()->randomElement($sponsors),
            'phone' => '01'. fake()->randomElement(['2','3','7','9']) . fake()->numerify('#######'),
            'jumlah_tajaan' => $jumlahTajaan,
            'jenis_tajaan' => $eventType,
            'sponsor_coverage' => $coverage,
            'payment_status' => fake()->randomElement([
                'PENDING', 'PENDING', 'PENDING', 
                'PAID', 'PAID', 'PAID',]),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
