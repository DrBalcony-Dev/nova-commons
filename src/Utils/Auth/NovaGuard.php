<?php

namespace DrBalcony\NovaCommon\Utils\Auth;

use BadMethodCallException;
use DrBalcony\NovaCommon\Models\User;
use DrBalcony\NovaCommon\Services\AuthenticationService;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Class NovaGuard
 */
class NovaGuard implements Guard
{
    const GUARD_NAME = 'nova';

    /**
     * Auth user instance
     *
     * @var null|Authenticatable|User
     */
    protected $user;

    /**
     * Request from out
     *
     * @var Request
     */
    protected $request;

    /**
     * OpenAPIGuard constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Check whether user is logged in.
     *
     * @return bool
     */
    public function check(): bool
    {
        return (bool) $this->user();
    }

    /**
     * Check whether user is not logged in.
     *
     * @return bool
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Return user id or null.
     *
     * @return null|int|string
     */
    public function id(): null|int|string
    {
        $user = $this->user();

        return $user->uuid ?? null;
    }

    /**
     * Return user account_id or null.
     *
     * @return null|int|string
     */
    public function accountId(): null|int|string
    {
        $user = $this->user();

        return $user->account_uuid ?? null;
    }

    /**
     * Return user roles or []].
     *
     * @return array|null
     */
    public function roles(): null|array
    {
        $user = $this->user();

        return $user->roles ?? [];
    }

    /**
     * Manually set user as logged in.
     *
     * @param User|null $user
     * @return $this
     */
    public function setUser(?Authenticatable $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Validate credentials
     *
     * @param array $credentials
     * @return bool
     */
    public function validate(array $credentials = []): bool
    {
        throw new BadMethodCallException('Unexpected method call');
    }

    /**
     * Return user or throw AuthenticationException.
     *
     * @return User
     *
     * @throws AuthenticationException
     */
    public function authenticate(): User
    {
        $user = $this->user();
        if ($user instanceof User) {
            return $user;
        }
        throw new AuthenticationException;
    }

    /**
     * Return cached user or newly authenticate user.
     *
     * @return User|null
     */
    public function user(): ?User
    {
        return $this->user ?: $this->signInWithEarth();
    }

    /**
     * Sign in using jwt
     *
     * @return null|User
     */
    protected function signInWithEarth(): ?User
    {
        $token = $this->request->bearerToken();

        if (!$token) {
            Log::warning('Authentication token missing', [
                'ip' => $this->request->ip(),
                'uri' => $this->request->uri()
            ]);

            return null;
        }

        try {
            $service = app(AuthenticationService::class);
            $userData = $service->getUserWithToken($token);

            if (empty($userData)) {
                return null;
            }

            return new User($userData);
        } catch (Exception $e) {
            Log::error('Authentication failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Logout user.
     */
    public function logout(): void
    {
        if ($this->user) {
            $this->setUser(null);
        }
    }

    public function hasUser(): bool
    {
        return !empty($this->user);
    }
}
