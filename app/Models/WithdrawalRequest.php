<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    protected $fillable = [
        'number', 'user_id', 'amount', 'iban', 'account_holder', 'status',
        'admin_note', 'processed_by', 'processed_at', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2', 'processed_at' => 'datetime', 'paid_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
