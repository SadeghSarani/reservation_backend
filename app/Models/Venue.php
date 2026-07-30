<?php

namespace App\Models;

use Abbasudo\Purity\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    use Filterable;

    protected $fillable = [
        'owner_id',
        'name',
        'type',
        'billing_type',
        'price',
        'address',
        'capacity',
        'description',
        'is_active',
        'additionals',
    ];

    protected $casts = [
        'additionals' => 'array',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function venuePrice()
    {
        return $this->hasMany(VenueTimePrice::class);
    }

    public function educationalClasses()
    {
        return $this->hasMany(EducationalClass::class);
    }

    public function images()
    {
        return $this->hasManyThrough(
            File::class,
            VenueImage::class,
            'venue_id',
            'id',
            'id',
            'file_id'
        )->select('files.id', 'files.uuid');
    }
}
