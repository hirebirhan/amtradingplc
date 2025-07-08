<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Utilities\ConnectionChecker;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckDatabaseConnection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check database connectivity
        if (!ConnectionChecker::isDatabaseConnected()) {
            Log::critical('Database connection unavailable');
            
            // For API requests, return a JSON response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Database connection unavailable',
                    'error' => 'The system is currently experiencing technical difficulties'
                ], 503);
            }
            
            // For web requests that expect JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Database connection unavailable',
                    'error' => 'The system is currently experiencing technical difficulties'
                ], 503);
            }
            
            // For web requests, show the error view
            return response()->view('errors.generic', [
                'message' => 'Database connection unavailable',
                'error' => 'The system is currently experiencing technical difficulties. Please try again later.',
                'code' => 503
            ], 503);
        }
        
        return $next($request);
    }
} 