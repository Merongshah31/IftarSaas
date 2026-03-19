<?php


namespace App\Services;

use App\Models\Masjid;
use Illuminate\Support\Facades\Session;

class TenantContext
{
    /**
     * Session key for storing tenant ID.
     */
    const SESSION_KEY = 'tenant_masjid_id';

    /**
     * Static storage for tenant ID (for CLI/Tinker).
     */
    protected static ?int $tenantId = null;

    /**
     * Set the current tenant.
     *
     * @param int $masjidId
     * @return void
     */
    public static function setTenant(int $masjidId): void
    {
        // Store in both static property and session
        self::$tenantId = $masjidId;
        
        if (app()->runningInConsole() === false) {
            Session::put(self::SESSION_KEY, $masjidId);
        }
    }

    /**
     * Get the current tenant ID.
     *
     * @return int|null
     */
    public static function getTenantId(): ?int
    {
        // Priority: Static property > Session
        if (self::$tenantId !== null) {
            return self::$tenantId;
        }

        if (app()->runningInConsole() === false) {
            return Session::get(self::SESSION_KEY);
        }

        return null;
    }

    /**
     * Get the current tenant model.
     *
     * @return Masjid|null
     */
    public static function getTenant(): ?Masjid
    {
        $tenantId = self::getTenantId();
        
        if (!$tenantId) {
            return null;
        }

        return Masjid::find($tenantId);
    }

    /**
     * Check if a tenant is set.
     *
     * @return bool
     */
    public static function hasTenant(): bool
    {
        return self::getTenantId() !== null;
    }

    /**
     * Clear the current tenant.
     *
     * @return void
     */
    public static function clearTenant(): void
    {
        self::$tenantId = null;
        
        if (app()->runningInConsole() === false) {
            Session::forget(self::SESSION_KEY);
        }
    }

    /**
     * Execute a callback without tenant scope.
     *
     * @param callable $callback
     * @return mixed
     */
    public static function withoutTenant(callable $callback)
    {
        $currentTenant = self::getTenantId();
        
        self::clearTenant();
        
        try {
            return $callback();
        } finally {
            if ($currentTenant) {
                self::setTenant($currentTenant);
            }
        }
    }

    /**
     * Check if scope is registered
     */
    public static function isScopeRegistered(int $tenantId): bool
    {
        return self::getTenantId() === $tenantId;
    }
}