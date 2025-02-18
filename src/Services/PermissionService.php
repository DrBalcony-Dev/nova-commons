<?php

namespace DrBalcony\NovaCommon\Services;

use DrBalcony\NovaCommon\DTO\PermissionVerificationDto;
use DrBalcony\NovaCommon\Exceptions\PermissionVerificationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

/**
 * Service for handling permission verification
 */
class PermissionService
{
    private string $endpoint;

    public function __construct()
    {
        $this->endpoint = config('nova-common.permission.verify_endpoint');
    }

    /**
     * Verify if user has specific permission
     *
     * @throws PermissionVerificationException
     */
    public function hasPermission(string $token, PermissionVerificationDto $params): bool
    {
        try {
            $response = $this->makeRequest($token, $params);
            return $this->parseResponse($response);
        } catch (ConnectionException $e) {
            Log::error('Permission service connection failed', [
                'error' => $e->getMessage(),
                'endpoint' => $this->endpoint
            ]);
            throw new PermissionVerificationException(
                'Unable to connect to permission service',
                ResponseAlias::HTTP_SERVICE_UNAVAILABLE,
                $e,
                LogLevel::ERROR,
                true
            );
        } catch (\Exception $e) {
            Log::error('Permission verification failed', [
                'error' => $e->getMessage(),
                'params' => $params->toArray()
            ]);
            throw new PermissionVerificationException(
                "Permission verification failed: {$e->getMessage()}",
                $e->getCode() ?: ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
                $e,
                LogLevel::ERROR,
                true
            );
        }
    }

    /**
     * Make HTTP request to permission service
     *
     * @throws ConnectionException
     */
    private function makeRequest(string $token, PermissionVerificationDto $params): Response
    {
        return Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$token}"
        ])->post($this->endpoint, $params->toArray());
    }

    /**
     * Parse response from permission service
     *
     * @throws PermissionVerificationException
     */
    private function parseResponse(Response $response): bool
    {
        if ($response->status() === ResponseAlias::HTTP_UNAUTHORIZED) {
            throw new PermissionVerificationException(
                'Invalid or expired token',
                ResponseAlias::HTTP_UNAUTHORIZED,
                null,
                LogLevel::WARNING,
                true
            );
        }

        if (!$response->successful()) {
            throw new PermissionVerificationException(
                'Permission service request failed',
                $response->status(),
                null,
                LogLevel::ERROR,
                true
            );
        }

        $data = $response->json();

        if (!isset($data['success']) || !isset($data['data']['has_access'])) {
            throw new PermissionVerificationException(
                'Invalid response format from permission service',
                ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
                null,
                LogLevel::ERROR,
                true
            );
        }

        if (!$data['success']) {
            throw new PermissionVerificationException(
                $data['message'] ?? 'Permission verification failed',
                ResponseAlias::HTTP_FORBIDDEN,
                null,
                LogLevel::WARNING,
                true
            );
        }

        return (bool) $data['data']['has_access'];
    }
}