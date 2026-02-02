<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;

class ReservationPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function calculatePrice(Venue $venue, Carbon $start, ?Carbon $end, array $additionals = [])
    {
        $base = 0;

        if ($venue->billing_type === 'hourly') {
            $hours = $start->diffInHours($end);
            $base = $hours * $venue->price;
        }

        if ($venue->billing_type === 'monthly') {
            $base = $venue->price;
        }

        foreach ($additionals as $item) {
            $base += $item['price'];
        }

        return $base;
    }
}
