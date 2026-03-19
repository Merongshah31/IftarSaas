<?php

namespace Tests\Feature\Api;

use App\Models\Masjid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.require_invite_code', true);
        config()->set('admin.invite_code', 'UNIT-TEST-INVITE');
        config()->set('admin.master_key', 'UNIT-TEST-MASTER');
    }

    private function registerAdminAndGetToken(Masjid $masjid, string $email = 'admin@example.com'): string
    {
        $response = $this->postJson('/api/v1/admin/register', [
            'name' => 'Admin Masjid',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'masjid_id' => $masjid->id,
            'invite_code' => 'UNIT-TEST-INVITE',
        ]);

        $response->assertStatus(201);
        $userId = $response->json('data.user.id');

        // Approve the pending admin via master key
        $this->withHeaders(['X-Master-Key' => 'UNIT-TEST-MASTER'])
            ->postJson("/api/v1/superadmin/admins/{$userId}/approve")
            ->assertOk();

        // Login to receive the token
        $loginResponse = $this->postJson('/api/v1/admin/login', [
            'email' => $email,
            'password' => 'password123',
        ]);

        $loginResponse->assertOk();

        return (string) $loginResponse->json('data.token');
    }

    public function test_admin_register_requires_invite_code(): void
    {
        $masjid = Masjid::factory()->create();

        $response = $this->postJson('/api/v1/admin/register', [
            'name' => 'No Invite',
            'email' => 'noinvite@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'masjid_id' => $masjid->id,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['invite_code']);
    }

    public function test_admin_register_rejects_invalid_invite_code(): void
    {
        $masjid = Masjid::factory()->create();

        $response = $this->postJson('/api/v1/admin/register', [
            'name' => 'Bad Invite',
            'email' => 'badinvite@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'masjid_id' => $masjid->id,
            'invite_code' => 'WRONG-CODE',
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Invite code admin tidak sah',
            ]);
    }

    public function test_admin_can_register_and_access_panel_profile_without_tenant_header(): void
    {
        $masjid = Masjid::factory()->create();

        $token = $this->registerAdminAndGetToken($masjid);
        $this->assertNotEmpty($token);

        $profileResponse = $this
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/masjid/profile');

        $profileResponse
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $masjid->id,
                ],
            ]);
    }

    public function test_admin_login_returns_token_for_existing_account(): void
    {
        $masjid = Masjid::factory()->create();

        $this->registerAdminAndGetToken($masjid, 'login@example.com');

        $loginResponse = $this->postJson('/api/v1/admin/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNotEmpty($loginResponse->json('data.token'));
    }

    public function test_admin_login_fails_with_invalid_password(): void
    {
        $masjid = Masjid::factory()->create();
        $this->registerAdminAndGetToken($masjid, 'wrongpass@example.com');

        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'wrongpass@example.com',
            'password' => 'invalid-password',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Email atau kata laluan tidak sah',
            ]);
    }

    public function test_admin_me_requires_token(): void
    {
        $response = $this->getJson('/api/v1/admin/me');

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token admin diperlukan',
            ]);
    }

    public function test_admin_me_rejects_invalid_token(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer invalid-token'])
            ->getJson('/api/v1/admin/me');

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token admin tidak sah',
            ]);
    }

    public function test_dashboard_uses_admin_tenant_even_when_header_is_different(): void
    {
        $adminMasjid = Masjid::factory()->create();
        $otherMasjid = Masjid::factory()->create();
        $token = $this->registerAdminAndGetToken($adminMasjid, 'tenant-lock@example.com');

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'X-Tenant-ID' => (string) $otherMasjid->id,
            ])
            ->getJson('/api/v1/masjid/profile');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $adminMasjid->id,
                ],
            ]);
    }

    public function test_admin_logout_invalidates_token(): void
    {
        $masjid = Masjid::factory()->create();
        $token = $this->registerAdminAndGetToken($masjid, 'logout@example.com');

        $this
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/admin/logout')
            ->assertOk();

        $this
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/admin/me')
            ->assertStatus(401);
    }

    public function test_expired_token_is_rejected_and_revoked(): void
    {
        $masjid = Masjid::factory()->create();
        $token = $this->registerAdminAndGetToken($masjid, 'expired@example.com');

        $user = User::where('email', 'expired@example.com')->firstOrFail();
        $user->api_token_expires_at = now()->subMinute();
        $user->save();

        $this
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/admin/me')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token admin telah tamat tempoh',
            ]);

        $user->refresh();
        $this->assertNull($user->api_token);
        $this->assertNull($user->api_token_expires_at);
    }

    public function test_new_login_rotates_token_and_invalidates_previous_token(): void
    {
        $masjid = Masjid::factory()->create();
        $firstToken = $this->registerAdminAndGetToken($masjid, 'rotate@example.com');

        $secondLogin = $this->postJson('/api/v1/admin/login', [
            'email' => 'rotate@example.com',
            'password' => 'password123',
        ])->assertOk();

        $secondToken = (string) $secondLogin->json('data.token');
        $this->assertNotSame($firstToken, $secondToken);

        $this
            ->withHeaders(['Authorization' => 'Bearer '.$firstToken])
            ->getJson('/api/v1/admin/me')
            ->assertStatus(401);

        $this
            ->withHeaders(['Authorization' => 'Bearer '.$secondToken])
            ->getJson('/api/v1/admin/me')
            ->assertOk();
    }
}
