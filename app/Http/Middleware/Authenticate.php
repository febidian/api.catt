<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }

    public function handle($request, Closure $next, ...$guards)
    {
        try {
            if (!$request->hasCookie('jwt_token')) {
                return response()->json('Token expired', 401);
            }
            $rawToken = $request->cookie('jwt_token');

            $user = JWTAuth::setToken($rawToken)->authenticate();


            if (!$user) {
                throw new \Exception('User not found.');
            }
        } catch (\Exception $e) {
            if ($e instanceof TokenExpiredException) {
                return response()->json('Token expired', 401);
            }
            return response()->json('Unauthorized', 401);
        }


        return $next($request);
    }
}
