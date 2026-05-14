<?php

namespace App\Http\Middleware;

use App\Utilities\ConnectionChecker;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

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
        if (! ConnectionChecker::isDatabaseConnected()) {
            Log::critical('Database connection unavailable');

            return response()->json([
                'success' => false,
                'message' => 'Database connection unavailable',
                'error' => 'The system is currently experiencing technical difficulties',
            ], 503);
        }

        return $next($request);
    }
}
