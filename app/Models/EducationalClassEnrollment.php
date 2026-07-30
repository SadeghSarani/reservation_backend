<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationalClassEnrollment extends Model
{
    protected $fillable = [
        'educational_class_id', 'user_id', 'price', 'status', 'payment_status',
        'registered_at', 'cancelled_at',
    ];

    protected $casts = [
        'price' => 'decimal:2', 'registered_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    public function educationalClass()
    {
        return $this->belongsTo(EducationalClass::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
