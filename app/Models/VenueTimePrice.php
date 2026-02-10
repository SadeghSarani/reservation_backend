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
}
