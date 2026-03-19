<?php


namespace App\Services;

use App\Models\IftarDay;
use App\Models\Participant;

class CapacityService
{
    /**
     * Check if iftar day has available capacity.
     *
     * @param int $iftarDayId
     * @param int $requestedPax
     * @return bool
     */
    // Returns: true/false - Can we add 5 more people?
    public static function hasCapacity(int $iftarDayId, int $requestedPax): bool
    {
        $iftarDay = IftarDay::findOrFail($iftarDayId);
        
        $currentRegistered = $iftarDay->jumlah_daftar;
        $maxCapacity = $iftarDay->kapasiti_max;
        
        $availableSlots = $maxCapacity - $currentRegistered;
        
        return $availableSlots >= $requestedPax;
    }

    /**
     * Get available slots for an iftar day.
     *
     * @param int $iftarDayId
     * @return int
     */
    // Returns: 45 (if max=100, registered=55)
    public static function getAvailableSlots(int $iftarDayId): int
    {
        $iftarDay = IftarDay::findOrFail($iftarDayId);
        
        $currentRegistered = $iftarDay->jumlah_daftar;
        $maxCapacity = $iftarDay->kapasiti_max;
        
        return max(0, $maxCapacity - $currentRegistered);
    }

    /**
     * Calculate capacity percentage.
     *
     * @param int $iftarDayId
     * @return float
     */
    public static function getCapacityPercentage(int $iftarDayId): float
    {
        $iftarDay = IftarDay::findOrFail($iftarDayId);
        
        if ($iftarDay->kapasiti_max == 0) {
            return 0;
        }
        
        return round(($iftarDay->jumlah_daftar / $iftarDay->kapasiti_max) * 100, 2);
    }

    /**
     * Check if iftar day is full.
     *
     * @param int $iftarDayId
     * @return bool
     */
    // Returns: true/false
    public static function isFull(int $iftarDayId): bool
    {
        $iftarDay = IftarDay::findOrFail($iftarDayId);
        
        return $iftarDay->jumlah_daftar >= $iftarDay->kapasiti_max;
    }
}