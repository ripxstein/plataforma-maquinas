<?php

namespace App\Livewire;

use App\Services\MarkerLinkService;
use Livewire\Component;

class MarkerNavigation extends Component
{
    public function catalog(): array
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        return app(MarkerLinkService::class)->catalog();
    }

    public function resolveMarker(array $target): array
    {
        abort_unless(auth()->check(), 403);

        return app(MarkerLinkService::class)->resolve(auth()->id(), $target);
    }

    public function render()
    {
        return view('livewire.marker-navigation');
    }
}
