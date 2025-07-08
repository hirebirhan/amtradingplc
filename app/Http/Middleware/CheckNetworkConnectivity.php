<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

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
        $connected = @fsockopen("www.google.com", 80, $errno, $errstr, 2);

        if (!$connected) {
            Log::warning('Network connectivity issue detected');
            
            // For API requests, return a JSON response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Network connection unavailable',
                    'error' => 'Please check your internet connection and try again'
                ], 503);
            }
            
            // For web requests that expect JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Network connection unavailable',
                    'error' => 'Please check your internet connection and try again'
                ], 503);
            }
            
            // For web requests, show the error view
            return response()->view('errors.generic', [
                'message' => 'Network connection unavailable',
                'error' => 'Please check your internet connection and try again',
                'code' => 503
            ], 503);
        }
        
        return $next($request);
    }
} 