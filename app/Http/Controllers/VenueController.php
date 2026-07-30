<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\Venue;
use App\Models\VenueImage;
use App\Models\VenueTimePrice;
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
            'data' => Venue::filter()->paginate(10),
        ]);
    }

    public function getTime(Request $request, Venue $venue)
    {
        $data = VenueTimePrice::query()
            ->where('calendar_id', $request->get('calendar_id'))
            ->where('venue_id', $venue->id)
            ->with('reservation')
            ->get();

        return response()->json([
            'data' => $data,
            'message' => 'time',
        ]);
    }

    public function getAdminVenues(Request $request)
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
            'data' => Venue::filter()->paginate(10),
        ]);
    }

    public function getAdminVenue(Request $request, Venue $venue)
    {
        $user = auth()->user();

        if (! $user->isSuperAdmin() && $venue->owner_id !== $user->id) {
            return response()->json([
                'message' => 'not found',
            ], 404);
        }

        return response()->json([
            'message' => 'venues',
            'data' => $venue->load(['owner', 'images', 'venuePrice']),
        ]);
    }

    public function getCalendars(Request $request, Venue $venue)
    {

        $data = VenueTimePrice::query()
            ->where('venue_id', $venue->id)
            ->pluck('calendar_id')
            ->unique()
            ->toArray();

        return response()->json([
            'message' => 'calendar',
            'data' => Calendar::query()->whereIn('id', $data)->get(),
        ]);
    }

    public function getCalendarsData()
    {
        return response()->json([
            'message' => 'calendars',
            'data' => Calendar::all(),
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
        $this->authorize('update', $venue);

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
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'billing_type' => 'required|in:hourly,monthly,session',
            'address' => 'required|string',
            'capacity' => 'nullable|integer|min:0',
            'price' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'additionals' => 'nullable|array',
            'time_schedules' => 'nullable|array',
            'calendars_id' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {

            $venue = Venue::create([
                'owner_id' => auth()->id(),
                'name' => $request->name,
                'description' => $request->description,
                'address' => $request->address,
                'capacity' => $request->capacity ?? 0,
                'price' => $request->price ?? 0,
                'type' => $request->type,
                'billing_type' => $request->billing_type,
                'is_active' => $request->is_active,
                'additionals' => $request->additionals ?? [],
            ]);

            if (
                $request->billing_type === 'hourly' &&
                ! empty($request->time_schedules)
            ) {

                $calendarIds = $request->calendars_id ?? [null];

                foreach ($request->time_schedules as $schedule) {

                    $interval = (int) $schedule['interval_minutes'];

                    foreach ($schedule['ranges'] as $range) {

                        $start = \Carbon\Carbon::createFromFormat('H:i', $range['from']);
                        $end = \Carbon\Carbon::createFromFormat('H:i', $range['to']);

                        if ($start->gte($end)) {
                            continue;
                        }

                        while ($start->copy()->addMinutes($interval)->lte($end)) {

                            $slotEnd = $start->copy()->addMinutes($interval);

                            foreach ($calendarIds as $calendarId) {

                                VenueTimePrice::create([
                                    'venue_id' => $venue->id,
                                    'calendar_id' => $calendarId,
                                    'start_time' => $start->format('H:i:s'),
                                    'end_time' => $slotEnd->format('H:i:s'),
                                    'price' => $range['price'] ?? 0,
                                ]);
                            }

                            $start = $slotEnd;
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $venue->load('venuePrice'),
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
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
            'address',
            'capacity',
            'description',
            'is_active',
            'additionals',
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
