<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Masjid;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Masjid>
 */
class MasjidFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Masjid::class;



    public function definition(): array
    {

        //list of masjid names
        $masjidNames = [
            'Masjid Al-Falah',
            'Masjid An-Nur',
            'Masjid Al-Hidayah',
            'Masjid Al-Ikhlas',
            'Masjid Ar-Rahman',
            'Masjid At-Taqwa',
            'Masjid Al-Muttaqin',
            'Masjid Jamek',
            'Masjid Kampung',
            'Masjid Darul Ehsan',
        ];

        //malaysia states
        $negeri = [
            'Johor',
            'Kedah',
            'Kelantan',
            'Melaka',
            'Negeri Sembilan',
            'Pahang',
            'Perak',
            'Perlis',
            'Pulau Pinang',
            'Sabah',
            'Sarawak',
            'Selangor',
            'Terengganu',
        ];




        return [
            'nama_masjid' => fake()->randomElement($masjidNames),
            'alamat' => fake()->streetAddress() . ', ' . fake()->city(),
            'negeri' => fake()->randomElement($negeri),
            'contact_phone' => '01'. fake()->randomElement(['2','3','7','9']) . fake()->numerify('#######'),
            'logo_url' => fake()->optional(0.3)->imageUrl(200, 200, 'masjid', true, 'mosque'),
        ];

        
    }
}
