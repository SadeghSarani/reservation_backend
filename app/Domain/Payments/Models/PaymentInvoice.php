<?php

namespace App\Domain\Payments\Models;

use App\Models\Reservation;
use App\Models\User;
use App\Models\VenueTimePrice;
use Illuminate\Database\Eloquent\Model;

class PaymentInvoice extends Model
{
    protected $fillable = [
        'number', 'user_id', 'calendar_interval_id', 'educational_class_id', 'reservation_id',
        'enrollment_id', 'purpose', 'gateway',
        'amount', 'status', 'reference', 'reservation_data', 'metadata', 'expires_at', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reservation_data' => 'array',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function interval()
    {
        return $this->belongsTo(VenueTimePrice::class, 'calendar_interval_id');
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function educationalClass()
    {
        return $this->belongsTo(\App\Models\EducationalClass::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(\App\Models\EducationalClassEnrollment::class);
    }

}
