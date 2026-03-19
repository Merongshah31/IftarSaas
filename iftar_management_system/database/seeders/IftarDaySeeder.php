<?php

namespace Database\Seeders;

use App\Models\Masjid;
use App\Models\IftarDay;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class IftarDaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $masjids = Masjid::all();
        
        // Ramadan 2026 approximate dates
        $ramadanStart = Carbon::parse('2026-03-01');
        
        $masjids->each(function ($masjid) use ($ramadanStart) {
            // Create 10 sequential iftar days for each masjid
            for ($i = 0; $i < 10; $i++) {
                IftarDay::create([
                    'masjid_id' => $masjid->id,
                    'tarikh' => $ramadanStart->copy()->addDays($i),  // Sequential dates
                    'kapasiti_max' => fake()->randomElement([50, 100, 150, 200, 300]),
                    'jumlah_daftar' => 0,
                    'status' => fake()->randomElement(['open', 'open', 'open', 'full', 'closed']),
                    'notes' => fake()->optional(0.2)->sentence(),
                ]);
            }
        });
        
        $this->command->info("✅ Created " . ($masjids->count() * 10) . " iftar days");
    }
}
