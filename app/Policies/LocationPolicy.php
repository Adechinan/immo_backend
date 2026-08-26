<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    public function view(User $user, Location $location): bool
    {
        return $this->isOwnerOrAdmin($user, $location);
    }

    public function update(User $user, Location $location): bool
    {
        return $this->isOwnerOrAdmin($user, $location);
    }

    public function delete(User $user, Location $location): bool
    {
        return $this->isOwnerOrAdmin($user, $location);
    }

    /**
     * Enregistrer un paiement (mensualité) : le propriétaire (pour un locataire inscrit
     * ou non) ou le locataire lui-même (paiement en ligne) peuvent le faire.
     */
    public function recordPayment(User $user, Location $location): bool
    {
        return $this->isOwnerOrAdmin($user, $location) || $location->user_id === $user->id;
    }

    private function isOwnerOrAdmin(User $user, Location $location): bool
    {
        return $user->type === 'admin' || $location->bien->user_id === $user->id;
    }
}
