<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Connection\ConnectionException;
use Illuminate\Http\Client\ConnectionException as ClientConnectionException;
use PDOException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\App;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log all errors with additional context information
            Log::error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'user' => auth()->check() ? auth()->id() : 'guest'
            ]);
        });

        // In development mode, we skip all the custom renderable callbacks
        // to allow Laravel to show its detailed error pages
        if (config('app.debug')) {
            return;
        }

        // Handle database connection issues
        $this->renderable(function (QueryException $e, $request) {
            Log::error('Database query error: ' . $e->getMessage());
            return $this->handleApiException($request, $e, 'Database connection error', 500);
        });
        
        $this->renderable(function (PDOException $e, $request) {
            Log::error('Database PDO error: ' . $e->getMessage());
            return $this->handleApiException($request, $e, 'Database connection error', 500);
        });
        
        $this->renderable(function (ConnectionException $e, $request) {
            Log::error('Database connection error: ' . $e->getMessage());
            return $this->handleApiException($request, $e, 'Database connection error', 500);
        });

        // Handle network and API connection issues
        $this->renderable(function (ClientConnectionException $e, $request) {
            Log::error('Network connection error: ' . $e->getMessage());
            return $this->handleApiException($request, $e, 'Network connection error', 503);
        });

        // Handle generic HTTP exceptions
        $this->renderable(function (HttpException $e, $request) {
            $status = $e->getStatusCode();
            $message = match($status) {
                404 => 'Page not found',
                403 => 'Access forbidden',
                401 => 'Unauthorized access',
                419 => 'Session expired',
                429 => 'Too many requests',
                default => $e->getMessage() ?: 'An error occurred'
            };
            
            return $this->handleApiException($request, $e, $message, $status);
        });

        // Catch-all for any other exceptions
        $this->renderable(function (Throwable $e, $request) {
            Log::error('Uncaught exception: ' . $e->getMessage());
            return $this->handleApiException($request, $e, 'Something went wrong', 500);
        });
    }

    /**
     * Handle API exceptions with a consistent format
     */
    private function handleApiException($request, Throwable $exception, string $message = null, int $code = 500)
    {
        // For API requests, return JSON response
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $message ?? 'An unexpected error occurred',
                'error' => App::hasDebugModeEnabled() ? $exception->getMessage() : null,
            ], $code);
        }
        
        // For web requests, if AJAX or wants JSON, return JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message ?? 'An unexpected error occurred',
                'error' => App::hasDebugModeEnabled() ? $exception->getMessage() : null,
            ], $code);
        }

        // For web requests, if we have a specific error view for this code, use it
        if (view()->exists("errors.{$code}")) {
            return response()->view("errors.{$code}", [
                'message' => $message ?? 'An unexpected error occurred',
                'error' => App::hasDebugModeEnabled() ? $exception->getMessage() : null,
                'exception' => App::hasDebugModeEnabled() ? $exception : null
            ], $code);
        }

        // Otherwise fall back to generic error view
        return response()->view('errors.generic', [
            'message' => $message ?? 'An unexpected error occurred',
            'error' => App::hasDebugModeEnabled() ? $exception->getMessage() : null,
            'code' => $code,
            'exception' => App::hasDebugModeEnabled() ? $exception : null
        ], $code);
    }
} 