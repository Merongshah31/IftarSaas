<?php

namespace Tests\Feature\Api;

use App\Models\Masjid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApprovalApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.require_invite_code', true);
        config()->set('admin.invite_code', 'UNIT-TEST-INVITE');
        config()->set('admin.master_key', 'UNIT-TEST-MASTER');
    }

    private function registerPendingAdmin(Masjid $masjid, string $email = 'pending@example.com'): array
    {
        $response = $this->postJson('/api/v1/admin/register', [
            'name' => 'Admin Pending',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'masjid_id' => $masjid->id,
            'invite_code' => 'UNIT-TEST-INVITE',
        ]);

        $response->assertStatus(201);

        return (array) $response->json('data.user');
    }

    public function test_register_returns_pending_status_without_token(): void
    {
        $masjid = Masjid::factory()->create();

        $response = $this->postJson('/api/v1/admin/register', [
            'name' => 'Admin Pending',
            'email' => 'pending@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'masjid_id' => $masjid->id,
            'invite_code' => 'UNIT-TEST-INVITE',
        ]);

        $response
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'status' => 'pending',
                    ],
                ],
            ]);

        // No token in response
        $this->assertNull($response->json('data.token'));
    }

    public function test_admin_cannot_login_before_approval(): void
    {
        $masjid = Masjid::factory()->create();
        $this->registerPendingAdmin($masjid, 'unapproved@example.com');

        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'unapproved@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akaun anda belum diluluskan oleh superadmin. Sila hubungi pentadbir sistem.',
            ]);
    }

    public function test_superadmin_can_approve_pending_admin(): void
    {
        $masjid = Masjid::factory()->create();
        $userData = $this->registerPendingAdmin($masjid, 'approve@example.com');

        $response = $this
            ->withHeaders(['X-Master-Key' => 'UNIT-TEST-MASTER'])
            ->postJson("/api/v1/superadmin/admins/{$userData['id']}/approve");

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Akaun admin telah diluluskan',
            ]);

        $this->assertNotNull(User::find($userData['id'])->admin_approved_at);
    }

    public function test_approved_admin_can_login(): void
    {
        $masjid = Masjid::factory()->create();
        $userData = $this->registerPendingAdmin($masjid, 'approved@example.com');

        $this
            ->withHeaders(['X-Master-Key' => 'UNIT-TEST-MASTER'])
            ->postJson("/api/v1/superadmin/admins/{$userData['id']}/approve")
            ->assertOk();

        $loginResponse = $this->postJson('/api/v1/admin/login', [
            'email' => 'approved@example.com',
            'password' => 'password123',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotEmpty($loginResponse->json('data.token'));
    }

    public function test_pending_admins_list_returns_unapproved_accounts(): void
    {
        $masjid = Masjid::factory()->create();
        $this->registerPendingAdmin($masjid, 'list@example.com');

        $response = $this
            ->withHeaders(['X-Master-Key' => 'UNIT-TEST-MASTER'])
            ->getJson('/api/v1/superadmin/admins/pending');

        $response->assertOk()->assertJson(['success' => true]);

        $emails = array_column($response->json('data'), 'email');
        $this->assertContains('list@example.com', $emails);
    }

    public function test_pending_admins_list_excludes_approved_accounts(): void
    {
        $masjid = Masjid::factory()->create();
        $userData = $this->registerPendingAdmin($masjid, 'approved-list@example.com');

        $this
            ->withHeaders(['X-Master-Key' => 'UNIT-TEST-MASTER'])
            ->postJson("/api/v1/superadmin/admins/{$userData['id']}/approve")
            ->assertOk();

        $response = $this
            ->withHeaders(['X-Master-Key' => 'UNIT-TEST-MASTER'])
            ->getJson('/api/v1/superadmin/admins/pending');

        $response->assertOk();

        $emails = array_column($response->json('data'), 'email');
        $this->assertNotContains('approved-list@example.com', $emails);
    }

    public function test_approval_endpoint_requires_master_key(): void
    {
        $masjid = Masjid::factory()->create();
        $userData = $this->registerPendingAdmin($masjid, 'nokey@example.com');

        $response = $this->postJson("/api/v1/superadmin/admins/{$userData['id']}/approve");

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Master key tidak sah',
            ]);
    }

    public function test_pending_list_requires_master_key(): void
    {
        $response = $this->getJson('/api/v1/superadmin/admins/pending');

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Master key tidak sah',
            ]);
    }

    public function test_approving_already_approved_admin_returns_422(): void
    {
        $masjid = Masjid::factory()->create();
        $userData = $this->registerPendingAdmin($masjid, 'double@example.com');

        $this
            ->withHeaders(['X-Master-Key' => 'UNIT-TEST-MASTER'])
            ->postJson("/api/v1/superadmin/admins/{$userData['id']}/approve")
            ->assertOk();

        $response = $this
            ->withHeaders(['X-Master-Key' => 'UNIT-TEST-MASTER'])
            ->postJson("/api/v1/superadmin/admins/{$userData['id']}/approve");

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Akaun admin ini sudah diluluskan sebelumnya',
            ]);
    }

    public function test_full_admin_onboarding_flow_register_pending_approve_then_login(): void
    {
        $masjid = Masjid::factory()->create();

        $registerResponse = $this->postJson('/api/v1/admin/register', [
            'name' => 'Flow Admin',
            'email' => 'flow-admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'masjid_id' => $masjid->id,
            'invite_code' => 'UNIT-TEST-INVITE',
        ]);

        $registerResponse
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'status' => 'pending',
                    ],
                ],
            ]);

        $this->assertNull($registerResponse->json('data.token'));
        $userId = (int) $registerResponse->json('data.user.id');

        $pendingResponse = $this
            ->withHeaders(['X-Master-Key' => 'UNIT-TEST-MASTER'])
            ->getJson('/api/v1/superadmin/admins/pending');

        $pendingResponse->assertOk();
        $pendingIds = array_column((array) $pendingResponse->json('data'), 'id');
        $this->assertContains($userId, $pendingIds);

        $this
            ->withHeaders(['X-Master-Key' => 'UNIT-TEST-MASTER'])
            ->postJson("/api/v1/superadmin/admins/{$userId}/approve")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Akaun admin telah diluluskan',
            ]);

        $loginResponse = $this->postJson('/api/v1/admin/login', [
            'email' => 'flow-admin@example.com',
            'password' => 'password123',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNotEmpty($loginResponse->json('data.token'));
    }
}
