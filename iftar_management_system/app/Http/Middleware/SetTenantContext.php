<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\TenantContext;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        TenantContext::clearTenant();

        // Priority 1: From authenticated admin user
        if (auth()->check() && auth()->user()?->masjid_id) {
            TenantContext::setTenant((int) auth()->user()->masjid_id);
        }

        // Priority 2: From request header (used by public flow)
        if (!TenantContext::hasTenant() && $request->header('X-Tenant-ID')) {
            TenantContext::setTenant((int) $request->header('X-Tenant-ID'));
        }

        if (!TenantContext::hasTenant()) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant context required',
                'error' => 'Missing X-Tenant-ID header',
            ], 400);
        }

        // Option 3: From subdomain (advanced)
        // $subdomain = explode('.', $request->getHost())[0];
        // $masjid = Masjid::where('subdomain', $subdomain)->first();
        // if ($masjid) {
        //     TenantContext::setTenant($masjid->id);
        // }

        return $next($request);
    }
}