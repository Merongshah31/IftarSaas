<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MasjidResource;
use App\Models\Masjid;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MasjidController extends Controller
{
    /**
     * List public masjid options for frontend selection.
     */
    public function publicIndex(): JsonResponse
    {
        $masjids = Masjid::query()
            ->orderBy('nama_masjid')
            ->get();

        return response()->json([
            'success' => true,
            'data' => MasjidResource::collection($masjids),
        ]);
    }

    /**
     * Get current masjid profile.
     */
    public function profile(): JsonResponse
    {
        $masjidId = $this->resolveTenantId();

        $masjid = Masjid::with(['iftarDays' => function($q) {
            $q->where('tarikh', '>=', now()->toDateString())
              ->orderBy('tarikh', 'asc');
        }])->findOrFail($masjidId);

        return response()->json([
            'success' => true,
            'data' => new MasjidResource($masjid),
        ]);
    }

    /**
     * Update masjid profile.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_masjid' => ['sometimes', 'string', 'max:255'],
            'alamat' => ['sometimes', 'string'],
            'negeri' => ['sometimes', 'string', 'max:100'],
            'contact_phone' => ['sometimes', 'string', 'max:15'],
            'logo_url' => ['sometimes', 'url', 'nullable'],
        ]);

        $masjidId = $this->resolveTenantId();
        $masjid = Masjid::findOrFail($masjidId);
        
        $masjid->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil masjid berjaya dikemaskini',
            'data' => new MasjidResource($masjid->fresh()),
        ]);
    }

    /**
     * Get masjid dashboard statistics.
     */
    public function dashboard(): JsonResponse
    {
        $masjidId = $this->resolveTenantId();
        
        $masjid = Masjid::with(['iftarDays', 'sponsorships'])
            ->withCount(['iftarDays', 'sponsorships'])
            ->findOrFail($masjidId);

        $totalParticipants = $masjid->iftarDays->sum('jumlah_daftar');
        $totalSponsorship = $masjid->sponsorships->sum('jumlah_tajaan');
        $paidSponsorship = $masjid->sponsorships->where('payment_status', 'PAID')->sum('jumlah_tajaan');
        $upcomingEvents = $masjid->iftarDays()
            ->where('tarikh', '>=', now()->toDateString())
            ->where('status', 'open')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'masjid' => new MasjidResource($masjid),
                'statistics' => [
                    'total_iftar_days' => $masjid->iftar_days_count,
                    'upcoming_events' => $upcomingEvents,
                    'total_participants' => $totalParticipants,
                    'total_sponsorships' => $masjid->sponsorships_count,
                    'total_sponsorship_amount' => (float) $totalSponsorship,
                    'paid_sponsorship_amount' => (float) $paidSponsorship,
                    'pending_sponsorship_amount' => (float) ($totalSponsorship - $paidSponsorship),
                ],
            ],
        ]);
    }

    private function resolveTenantId(): int
    {
        $tenantId = TenantContext::getTenantId();

        if (!$tenantId) {
            abort(400, 'Tenant context required');
        }

        return $tenantId;
    }
}
