<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    // Only the provider (listing owner) can confirm, complete, or cancel
    public function manage(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->provider_id || $user->isAdmin();
    }
}
