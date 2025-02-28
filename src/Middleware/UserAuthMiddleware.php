<?php

namespace DrBalcony\NovaCommon\Middleware;

use Closure;
use DrBalcony\NovaCommon\Utils\Auth\NovaGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class UserAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (is_null(Auth::guard(NovaGuard::GUARD_NAME)->id())) {
            return Response::forbidden('Authentication is required to access this route.');
        }

        return $next($request);
    }
}
