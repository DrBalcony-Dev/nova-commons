<?php

namespace DrBalcony\NovaCommon\Middleware;

use Closure;
use DrBalcony\NovaCommon\DTO\PermissionVerificationDto;
use DrBalcony\NovaCommon\Exceptions\PermissionVerificationException;
use DrBalcony\NovaCommon\Services\PermissionService;
use DrBalcony\NovaCommon\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Log;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;
use function Laravel\Prompts\confirm;

/**
 * Middleware to verify permissions for incoming requests
 */
class CheckPermissionMiddleware
{
    use JsonResponseTrait;
    public function __construct(
        private readonly PermissionService $permissionService
    ) {}

    /**
     * Handle permission verification for the request
     *
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        $token = $request->bearerToken();

        if (!$token) {
            Log::warning('Authentication token missing', [
                'ip' => $request->ip(),
                'uri' => $request->uri()
            ]);
            throw new AuthenticationException('Authentication token not found');
        }

        try {
            $permissionDto = new PermissionVerificationDto($permission);

            if (!$this->permissionService->hasPermission($token, $permissionDto)) {
                if ($this->shouldLogPermissionDenial($permission)) {
                    Log::log(LogLevel::WARNING, 'Permission denied', [
                        'permission' => $permission,
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]);
                }
                return $this->sendError('Access denied for this permission' , [] , Response::HTTP_FORBIDDEN);
            }

            return $next($request);

        } catch (PermissionVerificationException $e) {
            if ($e->shouldReport()) {
                Log::log($e->getLevel(), 'Permission verification failed in middleware', [
                    'error' => $e->getMessage(),
                    'permission' => $permission,
                    'code' => $e->getCode()
                ]);
            }
            return $this->sendError($e->getMessage() , [] , Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * This can be extended to filter out noisy permissions
     */
    private function shouldLogPermissionDenial(string $permission): bool
    {
        return confirm('should_log_invalid_request');
    }
}