<?php

namespace Database\Seeders;

use App\Models\Masjid;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (!config('admin.enable_demo_account', true)) {
            $this->command?->warn('Demo admin seeding skipped: ADMIN_ENABLE_DEMO_ACCOUNT is false.');
            return;
        }

        $demoName = (string) config('admin.demo.name', 'Demo Admin Hackathon');
        $demoEmail = (string) config('admin.demo.email', 'demo.admin@hackathon.local');
        $demoPassword = (string) config('admin.demo.password', 'Demo12345!');

        $masjid = Masjid::query()->first();

        if (!$masjid) {
            $masjid = Masjid::create([
                'nama_masjid' => 'Masjid Demo Hackathon',
                'alamat' => 'Lot Demo, Jalan Inovasi, Kuala Lumpur',
                'negeri' => 'Kuala Lumpur',
                'contact_phone' => '01123456789',
                'logo_url' => null,
            ]);
        }

        $user = User::query()->firstOrNew(['email' => $demoEmail]);
        $user->name = $demoName;
        $user->password = $demoPassword;
        $user->masjid_id = $masjid->id;
        $user->admin_approved_at = now();
        $user->api_token = null;
        $user->api_token_expires_at = null;
        $user->save();

        $this->command?->info('Demo admin ready: '.$demoEmail.' / '.$demoPassword);
    }
}
