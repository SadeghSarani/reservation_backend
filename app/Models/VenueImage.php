<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenueImage extends Model
{
    protected $fillable = [
        'venue_id',
        'file_id'
    ];
}
