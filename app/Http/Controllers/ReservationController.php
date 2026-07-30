<?php

namespace App\Http\Controllers;

use App\Domain\Payments\Services\ReservationPaymentService;
use App\Models\Reservation;
use App\Models\Venue;
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
            ! $user->isSuperAdmin() &&
            ! ($user->isVenueAdmin() && $reservation->venue->owner_id === $user->id)
        ) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled',
        ]);

        $reservation->update([
            'status' => $request->status,
        ]);

        return response()->json($reservation);
    }

    public function reserveSlot(Request $request, ReservationPaymentService $payments)
    {
        $request->validate([
            'calendar_interval_id' => 'required|integer|exists:venue_time_prices,id',
            'additionals' => 'nullable|array',
            'additionals.*.name' => 'required|string',
        ]);

        try {
            $payment = $payments->initiate(auth()->id(), (int) $request->calendar_interval_id, $request->additionals ?? []);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json([
            'invoice' => $payment['invoice']->number,
            'amount' => $payment['invoice']->amount,
            'payment_url' => $payment['payment_url'],
        ], 201);
    }
}
