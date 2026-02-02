<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venue;

class VenuePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function update(User $user, Venue $venue)
    {
        return $user->isSuperAdmin() || $venue->owner_id === $user->id;
    }

    public function delete(User $user, Venue $venue)
    {
        return $user->isSuperAdmin() || $venue->owner_id === $user->id;
    }
}
