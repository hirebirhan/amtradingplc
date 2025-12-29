<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;
use App\Models\Transfer;

class SidebarComposer
{
    public function compose(View $view)
    {
        $user = auth()->user();
        $pendingTransfersCount = 0;

        if ($user && $user->can('transfers.view')) {
            $query = Transfer::where('status', 'pending');
            
            // Apply branch filtering for non-admin users
            if (!$user->isSuperAdmin() && !$user->isGeneralManager()) {
                if ($user->branch_id) {
                    $query->where(function ($q) use ($user) {
                        $q->where(function ($sq) use ($user) {
                            $sq->where('source_type', 'branch')->where('source_id', $user->branch_id);
                        })->orWhere(function ($sq) use ($user) {
                            $sq->where('destination_type', 'branch')->where('destination_id', $user->branch_id);
                        });
                    });
                }
            }
            
            $pendingTransfersCount = $query->count();
        }

        $view->with('pendingTransfersCount', $pendingTransfersCount);
    }
}