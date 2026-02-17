<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;

class UserController extends Controller
{


    public function dashboard()
    {
        $owner = auth()->user();

        $venueIds = $owner->venues()->pluck('id');

        $reservations = Reservation::whereIn('venue_id', $venueIds);

        return response()->json([
            'stats' => [
                'total' => (clone $reservations)->count(),
                'today' => (clone $reservations)
                    ->whereDate('start_at', today())
                    ->count(),
                'pending' => (clone $reservations)
                    ->where('status', 'pending')
                    ->count(),
                'success' => (clone $reservations)
                    ->where('status', 'success')
                    ->count(),
                'cancelled' => (clone $reservations)
                    ->where('status', 'cancelled')
                    ->count(),
                'totalRevenue' => (clone $reservations)
                    ->where('status', 'success')
                    ->sum('total_price'),
                'thisMonthRevenue' => (clone $reservations)
                    ->where('status', 'success')
                    ->whereMonth('start_at', now()->month)
                    ->whereYear('start_at', now()->year)
                    ->sum('total_price'),
            ],

            'recentReservations' => (clone $reservations)
                ->latest()
                ->take(10)
                ->with('user:id,name')
                ->get(),

            'pendingReservations' => (clone $reservations)
                ->where('status', 'pending')
                ->latest()
                ->take(5)
                ->with('user:id,name')
                ->get(),
        ]);
    }
}
