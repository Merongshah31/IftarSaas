<?php

namespace Tests\Feature\Api;

use App\Models\IftarDay;
use App\Models\Masjid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PublicFlowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_masjid_listing_does_not_require_tenant_header(): void
    {
        Masjid::factory()->count(2)->create();

        $response = $this->getJson('/api/v1/public/masjids');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_public_participant_registration_works_via_public_route(): void
    {
        $masjid = Masjid::factory()->create();

        $iftarDay = IftarDay::withoutGlobalScopes()->create([
            'masjid_id' => $masjid->id,
            'tarikh' => Carbon::tomorrow()->toDateString(),
            'kapasiti_max' => 20,
            'jumlah_daftar' => 0,
            'status' => 'open',
        ]);

        $response = $this->postJson("/api/v1/public/masjids/{$masjid->id}/participants", [
            'iftar_day_id' => $iftarDay->id,
            'nama' => 'Pengguna Awam',
            'no_telefon' => '012-3333333',
            'bil_pax' => 2,
            'notes' => 'Test public registration',
        ]);

        $response
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('participants', [
            'iftar_day_id' => $iftarDay->id,
            'nama' => 'Pengguna Awam',
            'no_telefon' => '012-3333333',
            'bil_pax' => 2,
        ]);
    }
}
