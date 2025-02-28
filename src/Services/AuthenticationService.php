<?php

namespace DrBalcony\NovaCommon\Services;

use DrBalcony\NovaCommon\Exceptions\AuthenticationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

/**
 * Service for handling nova authentication
 */
class AuthenticationService
{
    private string $endpoint;

    public function __construct()
    {
        $this->endpoint = config('nova-common.earth.base-url'). '/api/users/info';
    }

    /**
     * Verify if user has specific authentication
     *
     * @throws AuthenticationException
     */
    public function getUserWithToken(string $token): array
    {
        try {
            $response = Http::acceptJson()->withToken($token)->get($this->endpoint);
            return $this->parseResponse($response);
        } catch (ConnectionException $e) {
            Log::error('Authentication service connection failed', [
                'error' => $e->getMessage(),
                'endpoint' => $this->endpoint
            ]);
            throw new AuthenticationException(
                'Unable to connect to authentication service',
                ResponseAlias::HTTP_SERVICE_UNAVAILABLE,
                $e,
                LogLevel::ERROR,
                true
            );
        } catch (\Exception $e) {
            Log::error('Authentication failed', [
                'error' => $e->getMessage(),
            ]);
            throw new AuthenticationException(
                "Authentication failed: {$e->getMessage()}",
                $e->getCode() ?: ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
                $e,
                LogLevel::ERROR,
                true
            );
        }
    }

    /**
     * Parse response from authentication service
     *
     * @throws AuthenticationException
     */
    private function parseResponse(Response $response): array
    {
        if ($response->status() === ResponseAlias::HTTP_UNAUTHORIZED) {
            throw new AuthenticationException(
                'Invalid or expired token',
                ResponseAlias::HTTP_UNAUTHORIZED,
                null,
                LogLevel::WARNING,
                true
            );
        }

        if (!$response->successful()) {
            throw new AuthenticationException(
                'Authentication service request failed',
                $response->status(),
                null,
                LogLevel::ERROR,
                true
            );
        }

        $response = $response->json();

        if (!isset($response['success']) || !isset($response['data'])) {
            throw new AuthenticationException(
                'Invalid response format from authentication service',
                ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
                null,
                LogLevel::ERROR,
                true
            );
        }

        if (!$response['success']) {
            throw new AuthenticationException(
                $response['message'] ?? 'Authentication verification failed',
                ResponseAlias::HTTP_FORBIDDEN,
                null,
                LogLevel::WARNING,
                true
            );
        }

        return $response['data'];
    }
}