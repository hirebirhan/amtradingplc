<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckNetworkConnectivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check internet connectivity by pinging a reliable host
        $connected = @fsockopen('www.google.com', 80, $errno, $errstr, 2);

        if (! $connected) {
            Log::warning('Network connectivity issue detected');

            return response()->json([
                'success' => false,
                'message' => 'Network connection unavailable',
                'error' => 'Please check your internet connection and try again',
            ], 503);
        }

        return $next($request);
    }
}
