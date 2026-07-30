<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleUpgradeRequest extends Model
{
    protected $fillable = [
        'user_id', 'requested_role', 'business_name', 'phone', 'reason', 'status', 'pending_marker',
        'admin_note', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime', 'pending_marker' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
