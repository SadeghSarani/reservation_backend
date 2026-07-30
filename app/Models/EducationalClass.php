<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationalClass extends Model
{
    protected $fillable = [
        'instructor_id', 'venue_id', 'title', 'slug', 'description', 'category', 'level',
        'capacity', 'price', 'location', 'schedule', 'features', 'registration_deadline',
        'starts_on', 'ends_on', 'status',
    ];

    protected $casts = [
        'price' => 'decimal:2', 'schedule' => 'array', 'features' => 'array',
        'registration_deadline' => 'datetime', 'starts_on' => 'date', 'ends_on' => 'date',
    ];

    protected $appends = ['available_capacity'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function enrollments()
    {
        return $this->hasMany(EducationalClassEnrollment::class);
    }

    public function activeEnrollments()
    {
        return $this->enrollments()->where('status', 'registered');
    }

    public function getAvailableCapacityAttribute(): int
    {
        $count = array_key_exists('active_enrollments_count', $this->attributes)
            ? (int) $this->attributes['active_enrollments_count']
            : $this->activeEnrollments()->count();

        return max(0, $this->capacity - $count);
    }
}
