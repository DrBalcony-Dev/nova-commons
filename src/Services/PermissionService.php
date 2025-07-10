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
    private string $baseUrl;
    private string $legacyEndpoint;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('nova-common.earth.base-url'), '/');
        $this->legacyEndpoint = config('nova-common.permission.verify_endpoint');
    }

    /**
     * Verify if user has specific permission
     *
     * @throws PermissionVerificationException
     */
    public function hasPermission(string $token, PermissionVerificationDto $params): bool
    {
        try {
            $response = $this->makeRequest($this->legacyEndpoint, [
                'Authorization' => "Bearer {$token}"
            ], $params->toArray());
            return $this->parseResponse($response);
        } catch (ConnectionException $e) {
            $this->logError('Permission service connection failed', $e, ['endpoint' => $this->legacyEndpoint]);
            throw new PermissionVerificationException(
                'Unable to connect to permission service',
                ResponseAlias::HTTP_SERVICE_UNAVAILABLE,
                $e,
                LogLevel::ERROR,
                true
            );
        } catch (\Exception $e) {
            $this->logError('Permission verification failed', $e, ['params' => $params->toArray()]);
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
     * Get user permissions by UUID
     *
     * @throws PermissionVerificationException
     */
    public function getUserPermissions(string $userUuid): array
    {
        try {
            $endpoint = $this->baseUrl . "/api/permissions/{$userUuid}";
            $response = $this->makeGetRequest($endpoint);
            return $this->parseUserPermissionsResponse($response);
        } catch (ConnectionException $e) {
            $this->logError('Get user permissions connection failed', $e, ['endpoint' => $endpoint]);
            throw new PermissionVerificationException(
                'Unable to connect to permission service',
                ResponseAlias::HTTP_SERVICE_UNAVAILABLE,
                $e,
                LogLevel::ERROR,
                true
            );
        } catch (\Exception $e) {
            $this->logError('Get user permissions failed', $e, ['user_uuid' => $userUuid]);
            throw new PermissionVerificationException(
                "Get user permissions failed: {$e->getMessage()}",
                $e->getCode() ?: ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
                $e,
                LogLevel::ERROR,
                true
            );
        }
    }

    /**
     * Make HTTP POST request with authorization
     *
     * @throws ConnectionException
     */
    private function makeRequest(string $endpoint, array $headers, array $data): Response
    {
        return Http::withHeaders(array_merge([
            'Accept' => 'application/json',
            'client-token' => config('nova-common.earth.client-token' , 'test_token')
        ], $headers))->post($endpoint, $data);
    }

    /**
     * Make HTTP GET request without authorization
     *
     * @throws ConnectionException
     */
    private function makeGetRequest(string $endpoint): Response
    {
        return Http::withHeaders([
            'Accept' => 'application/json',
            'client-token' => config('nova-common.earth.client-token' , 'test_token')
        ])->get($endpoint);
    }

    /**
     * Parse response from permission service
     *
     * @throws PermissionVerificationException
     */
    private function parseResponse(Response $response): bool
    {
        $this->validateResponse($response);

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

    /**
     * Parse user permissions response
     *
     * @throws PermissionVerificationException
     */
    private function parseUserPermissionsResponse(Response $response): array
    {
        $this->validateResponse($response);

        $data = $response->json();

        if (!isset($data['success'])) {
            throw new PermissionVerificationException(
                'Invalid response format from Earth API',
                ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
                null,
                LogLevel::ERROR,
                true
            );
        }

        if (!$data['success']) {
            throw new PermissionVerificationException(
                $data['message'] ?? 'Get user permissions failed',
                ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
                null,
                LogLevel::ERROR,
                true
            );
        }

        return $data['data'] ?? [];
    }

    /**
     * Validate HTTP response
     *
     * @throws PermissionVerificationException
     */
    private function validateResponse(Response $response): void
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

        if ($response->status() === ResponseAlias::HTTP_NOT_FOUND) {
            throw new PermissionVerificationException(
                'Resource not found',
                ResponseAlias::HTTP_NOT_FOUND,
                null,
                LogLevel::WARNING,
                true
            );
        }

        if (!$response->successful()) {
            throw new PermissionVerificationException(
                'API request failed',
                $response->status(),
                null,
                LogLevel::ERROR,
                true
            );
        }
    }

    /**
     * Log error with context
     */
    private function logError(string $message, \Exception $e, array $context = []): void
    {
        Log::error($message, array_merge([
            'error' => $e->getMessage(),
        ], $context));
    }
}