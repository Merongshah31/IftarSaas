<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdminApi
{
    /**
     * Authenticate admin requests using Bearer token.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (!$plainToken) {
            // Backward compatibility: allow tenant-header based access when present.
            if ($request->header('X-Tenant-ID')) {
                return $next($request);
            }

            if ($request->is('api/v1/admin/*')) {
                return $this->unauthorized('Token admin diperlukan');
            }

            return response()->json([
                'success' => false,
                'message' => 'Tenant context required',
                'error' => 'Missing X-Tenant-ID header',
            ], 400);
        }

        $hashedToken = hash('sha256', $plainToken);
        $user = User::with('masjid')->where('api_token', $hashedToken)->first();

        if (!$user) {
            return $this->unauthorized('Token admin tidak sah');
        }

        if ($user->api_token_expires_at && $user->api_token_expires_at->isPast()) {
            $user->api_token = null;
            $user->api_token_expires_at = null;
            $user->save();

            return $this->unauthorized('Token admin telah tamat tempoh');
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }
}
