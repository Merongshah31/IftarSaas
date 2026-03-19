<?php

namespace Database\Seeders;

use App\Models\Masjid;
use App\Models\IftarDay;
use App\Models\Sponsorship;
use Illuminate\Database\Seeder;

class SponsorshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $masjids = Masjid::all();
        $iftarDays = IftarDay::all();
        
        $totalSponsorships = 0;
        
        // Create sponsorships for each masjid
        $masjids->each(function ($masjid) use ($iftarDays, &$totalSponsorships) {
            // Get iftar days for this masjid
            $masjidDays = $iftarDays->where('masjid_id', $masjid->id);
            
            // Create 5-10 sponsorships per masjid
            $count = rand(5, 10);
            
            for ($i = 0; $i < $count; $i++) {
                // 70% chance linked to specific day, 30% general
                $iftarDayId = rand(1, 10) <= 7 
                    ? $masjidDays->random()->id 
                    : null;
                
                Sponsorship::factory()->create([
                    'masjid_id' => $masjid->id,
                    'iftar_day_id' => $iftarDayId,
                ]);
            }
            
            $totalSponsorships += $count;
        });
        
        $this->command->info("✅ Created {$totalSponsorships} sponsorships");
    }
}
