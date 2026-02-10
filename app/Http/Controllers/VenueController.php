<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\VenueImage;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        if (isset($user)) {
            if ($user->isSuperAdmin()) {
                return Venue::with('owner')->paginate(10);
            }

            if ($user->isVenueAdmin()) {
                return Venue::where('owner_id', $user->id)
                    ->with('owner')
                    ->paginate(10);
            }
        }

        return response()->json([
            'message' => 'venues',
            'data' => Venue::filter()->paginate(10)
        ]);
    }


    public function dashboard()
    {
        $data = Venue::select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->get();

        return response()->json([
           'data' => $data,
           'message' => 'venues',
        ]);
    }

    public function uploadsPhoto(Request $request, Venue $venue)
    {
        foreach ($request->photo as $file) {

            $fileService = FileService::getInstance();
            $fileId = $fileService->saveFile(rand(10000, 99999), $file);

            VenueImage::create([
               'venue_id' => $venue->id,
               'file_id' => $fileId,
            ]);

        }

        return response()->json([
            'success' => true,
            'message' => 'Files uploaded successfully.',
        ]);
    }

    /**
     * GET /venues/{venue}
     */
    public function show(Venue $venue)
    {
        return $venue->load('owner', 'venuePrice', 'images');
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
            'additionals' => $request->additionals ?? '',
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
