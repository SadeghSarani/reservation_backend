<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    protected $guarded = [];

    protected $casts = [
        'day' => 'datetime',
        'holiday' => 'boolean',
    ];
}
