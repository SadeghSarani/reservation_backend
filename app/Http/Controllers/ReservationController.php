<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CalendarInterval;
use App\Models\Reservation;
use App\Models\Venue;
use App\Models\VenueTimePrice;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    /**
     * GET /reservations
     * - User        → own reservations
     * - Venue admin → reservations of own venues
     * - Super admin → all reservations
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return Reservation::with(['user', 'venue'])->paginate(10);
        }

        if ($user->isVenueAdmin()) {
            return Reservation::whereHas('venue', function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            })->with(['user', 'venue'])->paginate(10);
        }

        return Reservation::where('user_id', $user->id)
            ->with('venue')
            ->paginate(10);
    }

    /**
     * GET /reservations/{reservation}
     */
    public function show(Reservation $reservation)
    {
        $user = auth()->user();

        if (
            $user->isSuperAdmin() ||
            $reservation->user_id === $user->id ||
            ($user->isVenueAdmin() && $reservation->venue->owner_id === $user->id)
        ) {
            return $reservation->load(['user', 'venue']);
        }

        abort(403);
    }

    /**
     * POST /reservations
     */
    public function store(Request $request)
    {
        $request->validate([
            'venue_id' => 'required|exists:venues,id',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after:start_at',
        ]);

        $venue = Venue::findOrFail($request->venue_id);

        $start = Carbon::parse($request->start_at);
        $end = $request->end_at ? Carbon::parse($request->end_at) : null;

        $total = $service->calculatePrice(
            $venue,
            $start,
            $end,
            $request->additionals ?? []
        );

        $reservation = Reservation::create([
            'user_id' => auth()->id(),
            'venue_id' => $venue->id,
            'start_at' => $start,
            'end_at' => $end,
            'total_price' => $total,
            'additionals' => $request->additionals,
            'status' => 'pending',
        ]);

        return response()->json($reservation, 201);
    }

    /**
     * PATCH /reservations/{reservation}/status
     * (venue_admin / super_admin)
     */
    public function updateStatus(Request $request, Reservation $reservation)
    {
        $user = auth()->user();

        if (
            !$user->isSuperAdmin() &&
            !($user->isVenueAdmin() && $reservation->venue->owner_id === $user->id)
        ) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled'
        ]);

        $reservation->update([
            'status' => $request->status
        ]);

        return response()->json($reservation);
    }

    public function reserveSlot(Request $request)
    {
        $request->validate([
            'calendar_interval_id' => 'required',
            'user_id' => 'required|exists:users,id',
            'additionals' => 'nullable|array',
        ]);

        $interval = VenueTimePrice::findOrFail($request->calendar_interval_id);

        $additionalsPrice = collect($request->additionals ?? [])->sum('price');
        $totalPrice = $interval->price + $additionalsPrice;

        $exists = Reservation::where('calendar_interval_id', $interval->id)
            ->where('start_at', $interval->calendar->date . ' ' . $interval->start_time)
            ->where('end_at', $interval->calendar->date . ' ' . $interval->end_time)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Slot already reserved'], 409);
        }

        $reservation = Reservation::create([
            'user_id' => $request->user_id,
            'venue_id' => $interval->venue_id,
            'calendar_interval_id' => $interval->id,
            'start_at' => $interval->calendar->date . ' ' . $interval->start_time,
            'end_at' => $interval->calendar->date . ' ' . $interval->end_time,
            'total_price' => $totalPrice,
            'status' => 'pending',
            'additionals' => $request->additionals ?? []
        ]);

        return response()->json($reservation, 201);
    }

}

