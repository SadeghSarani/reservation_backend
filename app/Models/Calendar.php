<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    protected $fillable = ['venue_id','name','date'];

    public function venue() {
        return $this->belongsTo(Venue::class);
    }

    public function intervals() {
        return $this->hasMany(CalendarInterval::class);
    }
}

