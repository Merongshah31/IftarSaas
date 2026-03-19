<?php

namespace Tests\Feature\Api;

use App\Models\IftarDay;
use App\Models\Masjid;
use App\Models\Participant;
use App\Models\Sponsorship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BusinessRulesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_header_is_required_for_domain_api(): void
    {
        $response = $this->getJson('/api/v1/iftar-days');

        $response
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Tenant context required',
            ]);
    }

    public function test_registration_rejected_when_event_closed(): void
    {
        $masjid = Masjid::factory()->create();
        $iftarDay = IftarDay::withoutGlobalScopes()->create([
            'masjid_id' => $masjid->id,
            'tarikh' => Carbon::tomorrow()->toDateString(),
            'kapasiti_max' => 20,
            'jumlah_daftar' => 0,
            'status' => 'closed',
        ]);

        $response = $this
            ->withHeaders(['X-Tenant-ID' => (string) $masjid->id])
            ->postJson('/api/v1/participants', [
                'iftar_day_id' => $iftarDay->id,
                'nama' => 'Ali Bin Omar',
                'no_telefon' => '012-1234567',
                'bil_pax' => 1,
            ]);

        $response->assertStatus(400);
        $this->assertStringContainsString(
            'Registration is closed',
            (string) $response->json('error')
        );
    }

    public function test_duplicate_registration_rejected_for_same_phone_day_and_tenant(): void
    {
        $masjid = Masjid::factory()->create();
        $iftarDay = IftarDay::withoutGlobalScopes()->create([
            'masjid_id' => $masjid->id,
            'tarikh' => Carbon::tomorrow()->toDateString(),
            'kapasiti_max' => 20,
            'jumlah_daftar' => 0,
            'status' => 'open',
        ]);

        $payload = [
            'iftar_day_id' => $iftarDay->id,
            'nama' => 'Abu Bakar',
            'no_telefon' => '013-7654321',
            'bil_pax' => 1,
        ];

        $first = $this
            ->withHeaders(['X-Tenant-ID' => (string) $masjid->id])
            ->postJson('/api/v1/participants', $payload);

        $first->assertStatus(201);

        $second = $this
            ->withHeaders(['X-Tenant-ID' => (string) $masjid->id])
            ->postJson('/api/v1/participants', $payload);

        $second->assertStatus(400);
        $this->assertStringContainsString(
            'Duplicate registration',
            (string) $second->json('error')
        );
    }

    public function test_cancel_from_full_event_auto_reopens_event(): void
    {
        $masjid = Masjid::factory()->create();
        $iftarDay = IftarDay::withoutGlobalScopes()->create([
            'masjid_id' => $masjid->id,
            'tarikh' => Carbon::tomorrow()->toDateString(),
            'kapasiti_max' => 1,
            'jumlah_daftar' => 0,
            'status' => 'open',
        ]);

        $registerResponse = $this
            ->withHeaders(['X-Tenant-ID' => (string) $masjid->id])
            ->postJson('/api/v1/participants', [
                'iftar_day_id' => $iftarDay->id,
                'nama' => 'Siti Aminah',
                'no_telefon' => '017-1111111',
                'bil_pax' => 1,
            ]);

        $registerResponse->assertStatus(201);

        $iftarDay = IftarDay::withoutGlobalScopes()->findOrFail($iftarDay->id);
        $this->assertSame('full', $iftarDay->status);
        $this->assertSame(1, $iftarDay->jumlah_daftar);

        $participantId = $registerResponse->json('data.id');

        $cancelResponse = $this
            ->withHeaders(['X-Tenant-ID' => (string) $masjid->id])
            ->postJson("/api/v1/participants/{$participantId}/cancel");

        $cancelResponse->assertOk();

        $iftarDay = IftarDay::withoutGlobalScopes()->findOrFail($iftarDay->id);
        $this->assertSame('open', $iftarDay->status);
        $this->assertSame(0, $iftarDay->jumlah_daftar);
    }

    public function test_sponsorship_paid_status_is_final(): void
    {
        $masjid = Masjid::factory()->create();
        $iftarDay = IftarDay::withoutGlobalScopes()->create([
            'masjid_id' => $masjid->id,
            'tarikh' => Carbon::tomorrow()->toDateString(),
            'kapasiti_max' => 20,
            'jumlah_daftar' => 0,
            'status' => 'open',
        ]);

        $sponsorship = Sponsorship::withoutGlobalScopes()->create([
            'masjid_id' => $masjid->id,
            'iftar_day_id' => $iftarDay->id,
            'nama_sponsor' => 'Syarikat Maju',
            'phone' => '0123456789',
            'jumlah_tajaan' => 500.00,
            'jenis_tajaan' => 'IFTAR',
            'sponsor_coverage' => 'PARTIAL',
            'payment_status' => 'PENDING',
            'notes' => null,
        ]);

        $markPaid = $this
            ->withHeaders(['X-Tenant-ID' => (string) $masjid->id])
            ->postJson("/api/v1/sponsorships/{$sponsorship->id}/mark-paid");

        $markPaid->assertOk();

        $updateAttempt = $this
            ->withHeaders(['X-Tenant-ID' => (string) $masjid->id])
            ->putJson("/api/v1/sponsorships/{$sponsorship->id}", [
                'iftar_day_id' => $iftarDay->id,
                'nama_sponsor' => 'Syarikat Maju',
                'phone' => '0123456789',
                'jumlah_tajaan' => 500.00,
                'jenis_tajaan' => 'IFTAR',
                'sponsor_coverage' => 'PARTIAL',
                'payment_status' => 'FAILED',
                'notes' => 'try downgrade status',
            ]);

        $updateAttempt
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertSame('PAID', Sponsorship::withoutGlobalScopes()->findOrFail($sponsorship->id)->payment_status);
    }

    public function test_last_slot_allows_only_one_registration_and_second_request_is_rejected(): void
    {
        $masjid = Masjid::factory()->create();
        $iftarDay = IftarDay::withoutGlobalScopes()->create([
            'masjid_id' => $masjid->id,
            'tarikh' => Carbon::tomorrow()->toDateString(),
            'kapasiti_max' => 1,
            'jumlah_daftar' => 0,
            'status' => 'open',
        ]);

        $first = $this
            ->withHeaders(['X-Tenant-ID' => (string) $masjid->id])
            ->postJson('/api/v1/participants', [
                'iftar_day_id' => $iftarDay->id,
                'nama' => 'Tester Pertama',
                'no_telefon' => '011-1111111',
                'bil_pax' => 1,
            ]);

        $second = $this
            ->withHeaders(['X-Tenant-ID' => (string) $masjid->id])
            ->postJson('/api/v1/participants', [
                'iftar_day_id' => $iftarDay->id,
                'nama' => 'Tester Kedua',
                'no_telefon' => '012-2222222',
                'bil_pax' => 1,
            ]);

        $first->assertStatus(201);
        $second->assertStatus(400);
        $this->assertStringContainsString(
            'Insufficient capacity',
            (string) $second->json('error')
        );

        $iftarDay->refresh();
        $this->assertSame(1, $iftarDay->jumlah_daftar);
        $this->assertSame('full', $iftarDay->status);

        $activeCount = Participant::withoutGlobalScopes()
            ->where('masjid_id', $masjid->id)
            ->where('iftar_day_id', $iftarDay->id)
            ->where('is_cancelled', false)
            ->count();

        $this->assertSame(1, $activeCount);
    }
}
