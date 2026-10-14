<?php

namespace App\Http\Livewire\Concerns;

trait EnsuresAdmin
{
    protected function allowedRoles(): array
    {
        return ['admin', 'super_admin'];
    }

    // Livewire calls hydrate() on every update request, so an action can't be
    // driven by someone who never passed the route middleware.
    public function hydrate()
    {
        $user = auth()->user();

        abort_unless($user && in_array($user->role, $this->allowedRoles(), true), 403);
    }
}