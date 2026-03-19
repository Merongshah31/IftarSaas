<?php

namespace App\Services;

use App\Models\Sponsorship;
use App\Models\Masjid;
use App\Models\IftarDay;
use Illuminate\Support\Collection;
use Exception;

class SponsorshipService
{
    /**
     * Create a new sponsorship.
     *
     * @param array $data
     * @return Sponsorship
     */
    public static function create(array $data): Sponsorship
    {
        $masjidId = $data['masjid_id'] ?? TenantContext::getTenantId();

        if (!$masjidId) {
            throw new Exception('Tenant masjid_id is required.');
        }

        return Sponsorship::create([
            'masjid_id' => $masjidId,
            'iftar_day_id' => $data['iftar_day_id'] ?? null,
            'nama_sponsor' => $data['nama_sponsor'],
            'phone' => $data['phone'],
            'jumlah_tajaan' => $data['jumlah_tajaan'],
            'jenis_tajaan' => $data['jenis_tajaan'],
            'sponsor_coverage' => $data['sponsor_coverage'],
            'payment_status' => $data['payment_status'] ?? 'PENDING',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Mark sponsorship as paid.
     *
     * @param int $sponsorshipId
     * @return bool
     * @throws Exception
     */
    public static function markAsPaid(int $sponsorshipId): bool
    {
        $sponsorship = Sponsorship::findOrFail($sponsorshipId);

        if ($sponsorship->payment_status === 'PAID') {
            throw new Exception('Sponsorship already marked as paid.');
        }

        $sponsorship->update(['payment_status' => 'PAID']);
        
        return true;
    }

    /**
     * Get total sponsorship amount for a masjid.
     *
     * @param int $masjidId
     * @param string|null $paymentStatus
     * @return float
     */
    public static function getTotalAmount(int $masjidId, ?string $paymentStatus = null): float
    {
        $query = Sponsorship::where('masjid_id', $masjidId);

        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        return $query->sum('jumlah_tajaan');
    }

    /**
     * Get sponsorships by iftar day.
     *
     * @param int $iftarDayId
     * @return Collection
     */
    public static function getByIftarDay(int $iftarDayId): Collection
    {
        return Sponsorship::where('iftar_day_id', $iftarDayId)->get();
    }

    /**
     * Get general sponsorships (not linked to specific day).
     *
     * @param int $masjidId
     * @return Collection
     */
    public static function getGeneralSponsorships(int $masjidId): Collection
    {
        return Sponsorship::where('masjid_id', $masjidId)
            ->whereNull('iftar_day_id')
            ->get();
    }

    /**
     * Get sponsorship statistics for a masjid.
     *
     * @param int $masjidId
     * @return array
     */
    public static function getStatistics(int $masjidId): array
    {
        $sponsorships = Sponsorship::where('masjid_id', $masjidId)->get();

        return [
            'total_sponsorships' => $sponsorships->count(),
            'total_amount' => $sponsorships->sum('jumlah_tajaan'),
            'paid_amount' => $sponsorships->where('payment_status', 'PAID')->sum('jumlah_tajaan'),
            'pending_amount' => $sponsorships->where('payment_status', 'PENDING')->sum('jumlah_tajaan'),
            'by_coverage' => [
                'full' => $sponsorships->where('sponsor_coverage', 'FULL')->sum('jumlah_tajaan'),
                'partial' => $sponsorships->where('sponsor_coverage', 'PARTIAL')->sum('jumlah_tajaan'),
                'general' => $sponsorships->where('sponsor_coverage', 'GENERAL')->sum('jumlah_tajaan'),
            ],
            'by_type' => [
                'iftar' => $sponsorships->where('jenis_tajaan', 'IFTAR')->sum('jumlah_tajaan'),
                'moreh' => $sponsorships->where('jenis_tajaan', 'MOREH')->sum('jumlah_tajaan'),
            ],
        ];
    }

    /**
     * Get top sponsors for a masjid.
     *
     * @param int $masjidId
     * @param int $limit
     * @return Collection
     */
    public static function getTopSponsors(int $masjidId, int $limit = 10): Collection
    {
        return Sponsorship::where('masjid_id', $masjidId)
            ->where('payment_status', 'PAID')
            ->orderBy('jumlah_tajaan', 'desc')
            ->limit($limit)
            ->get();
    }
}