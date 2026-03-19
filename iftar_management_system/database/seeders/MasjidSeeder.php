<?php

namespace Database\Seeders;

use App\Models\Masjid;
use Illuminate\Database\Seeder;

class MasjidSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    

    public function run(): void
    {
        // Create 5 masjids
        Masjid::factory(5)->create();
        
        $this->command->info('✅ Created 5 masjids');
    }
}
