<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class SuperadminController extends Controller
{
    public function pendingAdmins(): JsonResponse
    {
        $pending = User::with('masjid')
            ->whereNotNull('masjid_id')
            ->whereNull('admin_approved_at')
            ->select(['id', 'name', 'email', 'masjid_id', 'created_at'])
            ->latest()
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'masjid_id' => $user->masjid_id,
                'masjid' => $user->masjid,
                'created_at' => $user->created_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => $pending,
        ]);
    }

    public function approveAdmin(int $userId): JsonResponse
    {
        $user = User::whereNotNull('masjid_id')->findOrFail($userId);

        if ($user->admin_approved_at !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Akaun admin ini sudah diluluskan sebelumnya',
            ], 422);
        }

        $user->admin_approved_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Akaun admin telah diluluskan',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'masjid_id' => $user->masjid_id,
                    'admin_approved_at' => $user->admin_approved_at,
                ],
            ],
        ]);
    }
}
