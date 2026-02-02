<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'user_id',
        'venue_id',
        'calendar_interval_id', // new field linking to interval
        'start_at',
        'end_at',
        'total_price',
        'status',
        'additionals'
    ];

    protected $casts = [
        'additionals' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function interval()
    {
        return $this->belongsTo(CalendarInterval::class, 'calendar_interval_id');
    }
}
