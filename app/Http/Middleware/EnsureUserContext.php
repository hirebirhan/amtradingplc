<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\User\UserContextService;
use Closure;
use Illuminate\Http\Request;

class EnsureUserContext
{
    public function __construct(private readonly UserContextService $context) {}

    public function handle(Request $request, Closure $next)
    {
        // Only enforce for authenticated users
        if ($request->user()) {
            // Touch the context to ensure fallbacks are applied; do not force redirects yet.
            $this->context->currentBranch($request->user());
            $this->context->currentWarehouse($request->user());
        }

        return $next($request);
    }
}
