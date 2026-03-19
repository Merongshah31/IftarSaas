<?php

namespace App\Services;

use App\Models\IftarDay;
use App\Models\Masjid;
use Illuminate\Support\Collection;
use Exception;

class IftarDayService
{
    /**
     * Create a new iftar day.
     *
     * @param array $data
     * @return IftarDay
     */
    public static function create(array $data): IftarDay
    {
        $masjidId = $data['masjid_id'] ?? TenantContext::getTenantId();

        if (!$masjidId) {
            throw new Exception('Tenant masjid_id is required.');
        }

        return IftarDay::create([
            'masjid_id' => $masjidId,
            'tarikh' => $data['tarikh'],
            'kapasiti_max' => $data['kapasiti_max'],
            'jumlah_daftar' => 0,
            'status' => 'open',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Update iftar day details.
     *
     * @param int $iftarDayId
     * @param array $data
     * @return IftarDay
     * @throws Exception
     */
    public static function update(int $iftarDayId, array $data): IftarDay
    {
        $iftarDay = IftarDay::findOrFail($iftarDayId);

        // Prevent capacity reduction below current registrations
        if (isset($data['kapasiti_max']) && $data['kapasiti_max'] < $iftarDay->jumlah_daftar) {
            throw new Exception('Cannot reduce capacity below current registration count.');
        }

        $iftarDay->update($data);

        // Auto-update status based on new capacity
        if (CapacityService::isFull($iftarDayId)) {
            $iftarDay->update(['status' => 'full']);
        } elseif (strtoupper((string) $iftarDay->status) === 'FULL') {
            $iftarDay->update(['status' => 'open']);
        }

        return $iftarDay->fresh();
    }

    /**
     * Close iftar day for registration.
     *
     * @param int $iftarDayId
     * @return bool
     */
    public static function close(int $iftarDayId): bool
    {
        $iftarDay = IftarDay::findOrFail($iftarDayId);
        
        $iftarDay->update(['status' => 'closed']);
        
        return true;
    }

    /**
     * Reopen closed iftar day.
     *
     * @param int $iftarDayId
     * @return bool
     * @throws Exception
     */
    public static function reopen(int $iftarDayId): bool
    {
        $iftarDay = IftarDay::findOrFail($iftarDayId);

        $nextStatus = CapacityService::isFull($iftarDayId) ? 'full' : 'open';
        $iftarDay->update(['status' => $nextStatus]);
        
        return true;
    }

    /**
     * Get available iftar days for a masjid.
     *
     * @param int $masjidId
     * @return Collection
     */
    public static function getAvailableDays(int $masjidId): Collection
    {
        return IftarDay::where('masjid_id', $masjidId)
            ->where('status', 'open')
            ->where('tarikh', '>=', now()->toDateString())
            ->orderBy('tarikh', 'asc')
            ->get();
    }

    /**
     * Get upcoming iftar days with available slots.
     *
     * @param int $masjidId
     * @param int $requiredPax
     * @return Collection
     */
    public static function getAvailableForPax(int $masjidId, int $requiredPax): Collection
    {
        $upcomingDays = self::getAvailableDays($masjidId);

        return $upcomingDays->filter(function ($iftarDay) use ($requiredPax) {
            return CapacityService::hasCapacity($iftarDay->id, $requiredPax);
        });
    }

    /**
     * Get statistics for an iftar day.
     *
     * @param int $iftarDayId
     * @return array
     */
    public static function getStatistics(int $iftarDayId): array
    {
        $iftarDay = IftarDay::with('participants')->findOrFail($iftarDayId);

        $checkedIn = $iftarDay->participants->where('checkin_status', 'CHECKED_IN')->count();
        $pending = $iftarDay->participants->where('checkin_status', 'PENDING')->count();
        $noShow = $iftarDay->participants->where('checkin_status', 'NO_SHOW')->count();
        $cancelled = $iftarDay->participants->where('is_cancelled', true)->count();

        return [
            'total_capacity' => $iftarDay->kapasiti_max,
            'total_registered' => $iftarDay->jumlah_daftar,
            'available_slots' => CapacityService::getAvailableSlots($iftarDayId),
            'capacity_percentage' => CapacityService::getCapacityPercentage($iftarDayId),
            'checked_in' => $checkedIn,
            'pending' => $pending,
            'no_show' => $noShow,
            'cancelled' => $cancelled,
            'status' => $iftarDay->status,
        ];
    }

    /**
     * Delete iftar day (only if no registrations).
     *
     * @param int $iftarDayId
     * @return bool
     * @throws Exception
     */
    public static function delete(int $iftarDayId): bool
    {
        $iftarDay = IftarDay::findOrFail($iftarDayId);

        if ($iftarDay->jumlah_daftar > 0) {
            throw new Exception('Cannot delete iftar day with existing registrations.');
        }

        $iftarDay->delete();
        
        return true;
    }
}