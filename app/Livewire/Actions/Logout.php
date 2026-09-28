<?php

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke(): void
    {
        $userId = Auth::id();

        Auth::guard('web')->logout();

        // Invalidar cache del usuario para que no queden datos stale
        if ($userId) {
            Cache::forget('auth_user_'.$userId);
        }

        Session::invalidate();
        Session::regenerateToken();
    }
}
