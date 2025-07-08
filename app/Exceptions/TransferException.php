<?php

namespace App\Exceptions;

use Exception;

class TransferException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Report the exception
     */
    public function report(): bool
    {
        // Log transfer-specific errors
        \Log::channel('transfers')->error($this->getMessage(), [
            'exception' => $this,
            'user_id' => auth()->id(),
            'trace' => $this->getTraceAsString(),
        ]);
        
        return false;
    }

    /**
     * Render the exception into an HTTP response
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Transfer Error',
                'message' => $this->getMessage(),
            ], 422);
        }

        return back()->with('error', $this->getMessage());
    }
} 