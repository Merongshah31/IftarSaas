<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\IftarDay;
use App\Models\Participant;


class ParticipantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $iftarDays = IftarDay::all();
        
        $totalParticipants = 0;
        
        // For each iftar day, create 3-8 participants
        $iftarDays->each(function ($iftarDay) use (&$totalParticipants) {
            $count = rand(3, 8);
            
            Participant::factory($count)->create([
                'iftar_day_id' => $iftarDay->id,
            ]);
            
            // Update jumlah_daftar
            $iftarDay->update([
                'jumlah_daftar' => $count,
            ]);
            
            $totalParticipants += $count;
        });
        
        $this->command->info("✅ Created {$totalParticipants} participants");
    }
}
