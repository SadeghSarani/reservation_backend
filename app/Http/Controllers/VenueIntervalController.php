<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Models\VenueTimePrice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VenueIntervalController extends Controller
{
    public function createIntervalTimeVenue(Request $request, Venue $venue)
    {
        $this->createVenueTimePrices(
            $request->input('calendar_id', null),
            $venue->id,
            $request->input('price_range'),
            $request->input('interval')
        );

        return response()->json([
           'success' => true,
           'message' => 'قیمت کذاری با موفقیت انجام شد'
        ]);
    }

    private function createVenueTimePrices(
        int $calendarId,
        int $venueId,
        array $priceRanges,
        int $intervalMinutes = 90 // 1h30m
    ): void
    {
        DB::transaction(function () use ($calendarId, $venueId, $priceRanges, $intervalMinutes) {

            foreach ($priceRanges as $range) {

                $start = Carbon::createFromFormat('H:i', $range['from']);
                $end   = Carbon::createFromFormat('H:i', $range['to']);

                while ($start->lt($end)) {

                    $slotEnd = (clone $start)->addMinutes($intervalMinutes);

                    if ($slotEnd->gt($end)) {
                        break;
                    }

                    VenueTimePrice::insert([
                        'calendar_id' => $calendarId,
                        'venue_id'    => $venueId,
                        'start_time'  => $start->format('H:i'),
                        'end_time'    => $slotEnd->format('H:i'),
                        'price'       => $range['price'],
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);

                    $start = $slotEnd;
                }
            }
        });
    }

}
