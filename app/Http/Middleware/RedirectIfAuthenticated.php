<?php

namespace App\Http\Middleware;

use App\Helpers\HasResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated as RedirectIfAuthenticatedBase;

class RedirectIfAuthenticated extends RedirectIfAuthenticatedBase
{
    use HasResponse;

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                if ($request->wantsJson()) {
                    return $this->failedWithMessage(__('user.authenticated.found'), 400);
                }
                return redirect($this->redirectTo($request));
            }
        }

        return $next($request);
    }
}
