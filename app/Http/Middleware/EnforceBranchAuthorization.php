<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceBranchAuthorization
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // SuperAdmins and GeneralManagers have full access
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return $next($request);
        }

        // Branch managers must have a branch assignment
        if ($user->isBranchManager() && !$user->branch_id) {
            abort(403, 'Branch manager must be assigned to a branch.');
        }

        // Prevent branch managers from tampering with branch_id in requests
        if ($user->isBranchManager()) {
            // Check branch_id tampering
            if ($request->has('branch_id') && $request->input('branch_id') != $user->branch_id) {
                abort(403, 'You cannot modify branch assignments.');
            }
            
            // For transfers: source_id must be user's branch, destination_id must be different
            if ($request->routeIs('admin.transfers.*')) {
                if ($request->has('source_id') && $request->input('source_id') != $user->branch_id) {
                    abort(403, 'You can only create transfers from your branch.');
                }
                if ($request->has('destination_id') && $request->input('destination_id') == $user->branch_id) {
                    abort(403, 'You cannot transfer to your own branch.');
                }
            }
        }

        return $next($request);
    }
}