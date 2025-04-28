<?php

namespace App\Http\Middleware;

use App\Helpers\Helper;
use Auth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIsUserOrHost
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is authenticated
        if (!Auth::check() || !(Auth::user()->role === 'host' || Auth::user()->role === 'user')) {
            return Helper::jsonErrorResponse('Unauthorized action', 403);
        }

        return $next($request);
    }
}
