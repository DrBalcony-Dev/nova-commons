<?php

namespace DrBalcony\NovaCommon\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use DrBalcony\NovaCommon\Traits\JsonResponseTrait;

class ClientAuthMiddleware
{
    use JsonResponseTrait;

    private $cache;

    public function __construct()
    {
        // Initialize the connection and channel once, and reuse them
        $this->cache = config('nova-common.earth.auth-cache-driver', 'redis') === 'redis' ?
            app('cache')->store('redis')->connection(config('nova-common.earth.auth-cache-connection', 'default'))
            : app('cache');
    }

    public function handle($request, Closure $next)
    {
        $clientToken = $request->header('Client-Token');
        if (!$clientToken) {
            return response()->json(['success' => false, 'message' => 'Client-Token not included.'], 400);
        }
        $cacheKey = "client_auth_{$clientToken}";
        $isValid = $this->cache->get($cacheKey);
        // If the cache doesn't exist, validate client
        if ($isValid === null) {
            $isValid = $this->validateClient($clientToken);
            // Only store in cache if the result is true
            if ($isValid) {
                $this->cache->put($cacheKey, true, 86400); // Cache for 1 day
            }
        }
        if (!$isValid) {
            return $this->sendError('Unauthorized', code: 401);
        }
        return $next($request);
    }

    private function validateClient($clientToken)
    {
        // Get the server URL from environment configuration
        $serverUrl = config('nova-common.earth.base-url');
        // Send GET request to the external API endpoint
        $response = Http::withHeaders([
            'Client-Token' => config('nova-common.earth.client-token'),
        ])->get("{$serverUrl}/api/clients/authorize-client/{$clientToken}");
        // Check if the response is successful and contains the expected data
        if ($response->successful()) {
            $responseData = $response->json();
            // Return valid_token from the response data
            return $responseData['data']['valid_token'] ?? false;
        }
        // If the request fails or doesn't return the correct data, return false
        return false;
    }
}