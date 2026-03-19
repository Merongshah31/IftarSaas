<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        /** 
        *User::factory()->create([
         *   'name' => 'Test User',
          * 'email' => 'test@example.com',
        *]); */

    $this->command->info('🌱 Starting database seeding...');
    $this->command->newLine();
        
        // Order matters! (for foreign keys)
        $this->call([
            MasjidSeeder::class,        // 1. Create masjids first
            IftarDaySeeder::class,      // 2. Create iftar days
            ParticipantSeeder::class,   // 3. Create participants
            SponsorshipSeeder::class,   // 4. Create sponsorships
            DemoAdminSeeder::class,     // 5. Ensure demo login for hackathon
        ]);
        
    $this->command->newLine();
    $this->command->info('🎉 Database seeding completed successfully!');
    }
}
