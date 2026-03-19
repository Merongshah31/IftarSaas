<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMasterKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $masterKey = (string) config('admin.master_key', '');

        if ($masterKey === '') {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi master key belum ditetapkan',
            ], 500);
        }

        $providedKey = (string) $request->header('X-Master-Key', '');

        if ($providedKey === '' || !hash_equals($masterKey, $providedKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Master key tidak sah',
            ], 401);
        }

        return $next($request);
    }
}
