<?php

namespace DrBalcony\NovaCommon\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class ClientAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        $clientId = $request->header('Client-Id');
        $clientToken = $request->header('Client-Token');

        if (!$clientId || !$clientToken) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $cacheKey = "client_auth_{$clientId}_{$clientToken}";
        $isValid = Cache::remember($cacheKey, 86400, function () use ($clientId, $clientToken) {
            return $this->validateClient($clientId, $clientToken);
        });

        if (!$isValid) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }

    private function validateClient($clientId, $clientToken)
    {
        // Call the external API to validate
        return true; // Simulate validation
    }
}