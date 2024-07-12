<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ShareMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (!$request->hasCookie('jwt_token')) {
                return $next($request);
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

        if (!$request->hasCookie('jwt_token')) {
            return $next($request);
        }
    }
}
