<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CalendarInterval extends Model
{
    protected $fillable = ['calendar_id','start_time','end_time','price','slot_duration'];

    public function calendar() {
        return $this->belongsTo(Calendar::class);
    }

    public function reservations() {
        return $this->hasMany(Reservation::class);
    }

    // Generate slots for this interval
    public function generateSlots()
    {
        $slots = [];
        $start = Carbon::createFromFormat('H:i', $this->start_time);
        $end = Carbon::createFromFormat('H:i', $this->end_time);
        $duration = $this->slot_duration * 60; // minutes

        while ($start->lt($end)) {
            $slotEnd = $start->copy()->addMinutes($duration);
            if ($slotEnd->gt($end)) $slotEnd = $end->copy();
            $slots[] = [
                'interval_id' => $this->id,
                'start_time' => $start->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
                'price' => $this->price,
                'status' => 'available'
            ];
            $start->addMinutes($duration);
        }

        return $slots;
    }
}

