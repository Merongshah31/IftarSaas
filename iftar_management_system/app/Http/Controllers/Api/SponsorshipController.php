<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sponsorship\StoreSponsorshipRequest;
use App\Http\Resources\SponsorshipResource;
use App\Models\Sponsorship;
use App\Services\SponsorshipService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SponsorshipController extends Controller
{
    /**
     * Display a listing of sponsorships.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Sponsorship::query();

        // Filter by iftar day
        if ($request->has('iftar_day_id')) {
            if ($request->iftar_day_id === 'null') {
                $query->whereNull('iftar_day_id'); // General sponsorships
            } else {
                $query->where('iftar_day_id', $request->iftar_day_id);
            }
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by coverage
        if ($request->has('sponsor_coverage')) {
            $query->where('sponsor_coverage', $request->sponsor_coverage);
        }

        // Filter by type
        if ($request->has('jenis_tajaan')) {
            $query->where('jenis_tajaan', $request->jenis_tajaan);
        }

        // Search by sponsor name or phone
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_sponsor', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Include relationships
        if ($request->has('include')) {
            $includes = explode(',', $request->include);
            $query->with($includes);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $sponsorships = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => SponsorshipResource::collection($sponsorships),
            'meta' => [
                'current_page' => $sponsorships->currentPage(),
                'total' => $sponsorships->total(),
                'per_page' => $sponsorships->perPage(),
                'last_page' => $sponsorships->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created sponsorship.
     */
    public function store(StoreSponsorshipRequest $request): JsonResponse
    {
        try {
            $sponsorship = SponsorshipService::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Tajaan berjaya direkodkan. Terima kasih atas sumbangan anda!',
                'data' => new SponsorshipResource($sponsorship->load(['masjid', 'iftarDay'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal merekod tajaan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified sponsorship.
     */
    public function show(Sponsorship $sponsorship): JsonResponse
    {
        // Load relationships if requested
        $includes = request()->get('include');
        if ($includes) {
            $sponsorship->load(explode(',', $includes));
        }

        return response()->json([
            'success' => true,
            'data' => new SponsorshipResource($sponsorship),
        ]);
    }

    /**
     * Update the specified sponsorship.
     */
    public function update(StoreSponsorshipRequest $request, Sponsorship $sponsorship): JsonResponse
    {
        try {
            $payload = $request->validated();

            if (
                $sponsorship->payment_status === 'PAID' &&
                isset($payload['payment_status']) &&
                $payload['payment_status'] !== 'PAID'
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status PAID adalah final dan tidak boleh diubah',
                ], 422);
            }

            $sponsorship->update($payload);

            return response()->json([
                'success' => true,
                'message' => 'Tajaan berjaya dikemaskini',
                'data' => new SponsorshipResource($sponsorship->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengemaskini tajaan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark sponsorship as paid.
     */
    public function markAsPaid(Sponsorship $sponsorship): JsonResponse
    {
        try {
            SponsorshipService::markAsPaid($sponsorship->id);

            return response()->json([
                'success' => true,
                'message' => 'Tajaan ditanda sebagai DIBAYAR',
                'data' => new SponsorshipResource($sponsorship->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengemaskini status pembayaran',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get sponsorship statistics.
     */
    public function statistics(): JsonResponse
    {
        $masjidId = $this->resolveTenantId();

        $stats = SponsorshipService::getStatistics($masjidId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get top sponsors.
     */
    public function topSponsors(Request $request): JsonResponse
    {
        $masjidId = $this->resolveTenantId();
        $limit = $request->get('limit', 10);

        $topSponsors = SponsorshipService::getTopSponsors($masjidId, $limit);

        return response()->json([
            'success' => true,
            'data' => SponsorshipResource::collection($topSponsors),
        ]);
    }

    /**
     * Remove the specified sponsorship.
     */
    public function destroy(Sponsorship $sponsorship): JsonResponse
    {
        try {
            $sponsorship->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tajaan berjaya dipadam',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memadam tajaan',
                'error' => $e->getMessage(),
            ], 400);
        }
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
