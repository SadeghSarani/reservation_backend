<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    /**
     * GET /venues
     * - User        → all venues
     * - Venue admin → own venues
     * - Super admin → all venues
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return Venue::with('owner')->paginate(10);
        }

        if ($user->isVenueAdmin()) {
            return Venue::where('owner_id', $user->id)
                ->with('owner')
                ->paginate(10);
        }

        // Normal users
        return Venue::paginate(10);
    }

    /**
     * GET /venues/{venue}
     */
    public function show(Venue $venue)
    {
        return $venue->load('owner');
    }

    /**
     * POST /venues (venue_admin / super_admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required',
            'billing_type' => 'required|in:hourly,monthly',
            'price' => 'required|numeric',
        ]);

        $venue = Venue::create([
            'owner_id' => auth()->id(),
            'name' => $request->name,
            'type' => $request->type,
            'billing_type' => $request->billing_type,
            'price' => $request->price,
            'additionals' => $request->additionals,
        ]);

        return response()->json($venue, 201);
    }

    /**
     * PUT /venues/{venue}
     */
    public function update(Request $request, Venue $venue)
    {
        $this->authorize('update', $venue);

        $venue->update($request->only([
            'name',
            'type',
            'billing_type',
            'price',
            'additionals'
        ]));

        return response()->json($venue);
    }

    /**
     * DELETE /venues/{venue}
     */
    public function destroy(Venue $venue)
    {
        $this->authorize('delete', $venue);

        $venue->delete();

        return response()->json(['message' => 'Venue deleted']);
    }
}
