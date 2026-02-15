<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenueTimePrice extends Model
{
    protected $guarded = [];


    public function calendar()
    {
        return $this->belongsTo(Calendar::class);
    }

    public function reservation()
    {
        return $this->hasOne(Reservation::class, 'calendar_interval_id', 'id');
    }
}
