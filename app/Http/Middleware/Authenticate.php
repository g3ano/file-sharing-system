<?php

namespace App\Http\Middleware;

use App\Helpers\HasResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as AuthenticateBase;

class Authenticate extends AuthenticateBase
{
    use HasResponse;

    protected function unauthenticated($request, array $guards)
    {
        if ($request->wantsJson()) {
            return $this->failedWithMessage(__('user.authenticated.unauthenticated'), 401);
        }

        throw new AuthenticationException(
            'Unauthenticated.',
            $guards,
            $this->redirectTo($request),
        );
    }
}
