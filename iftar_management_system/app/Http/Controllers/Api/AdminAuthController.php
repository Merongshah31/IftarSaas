<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    private const TOKEN_TTL_MINUTES = 720;

    public function register(Request $request): JsonResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'masjid_id' => ['required', 'integer', 'exists:masjid,id'],
        ];

        if (config('admin.require_invite_code', true)) {
            $rules['invite_code'] = ['required', 'string', 'max:120'];
        }

        $validated = $request->validate($rules);

        if (config('admin.require_invite_code', true)) {
            $expectedInviteCode = (string) config('admin.invite_code', '');

            if ($expectedInviteCode === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Konfigurasi invite code admin belum ditetapkan',
                ], 500);
            }

            if (!hash_equals($expectedInviteCode, (string) $validated['invite_code'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invite code admin tidak sah',
                ], 403);
            }
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'masjid_id' => $validated['masjid_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Akaun admin berjaya dihantar untuk semakan. Sila tunggu kelulusan daripada superadmin.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'masjid_id' => $user->masjid_id,
                    'status' => 'pending',
                ],
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with('masjid')->where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau kata laluan tidak sah',
            ], 401);
        }

        if (!$user->masjid_id) {
            return response()->json([
                'success' => false,
                'message' => 'Akaun admin tidak dipautkan ke masjid',
            ], 403);
        }

        if ($user->admin_approved_at === null) {
            return response()->json([
                'success' => false,
                'message' => 'Akaun anda belum diluluskan oleh superadmin. Sila hubungi pentadbir sistem.',
            ], 403);
        }

        $plainToken = $this->issueToken($user);

        return response()->json([
            'success' => true,
            'message' => 'Log masuk berjaya',
            'data' => [
                'token' => $plainToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'masjid_id' => $user->masjid_id,
                ],
                'masjid' => $user->masjid,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'masjid_id' => $user->masjid_id,
                ],
                'masjid' => $user->masjid,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->api_token = null;
        $user->api_token_expires_at = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Log keluar berjaya',
        ]);
    }

    private function issueToken(User $user): string
    {
        $plainToken = Str::random(80);
        $user->api_token = hash('sha256', $plainToken);
        $user->api_token_expires_at = now()->addMinutes(self::TOKEN_TTL_MINUTES);
        $user->save();

        return $plainToken;
    }
}
