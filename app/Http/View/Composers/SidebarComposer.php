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
            $pendingTransfersCount = Transfer::where('status', 'pending')
                ->forUser($user)
                ->count();
        }

        $view->with('pendingTransfersCount', $pendingTransfersCount);
    }
}