<?php

namespace App\Services;

use App\Domain\Payments\Models\PaymentInvoice;
use App\Models\EducationalClassEnrollment;
use App\Models\User;
use App\Models\WithdrawalRequest;

class EarningsService
{
    public function balance(User $user): array
    {
        $venueRevenue = (float) PaymentInvoice::query()
            ->join('reservations', 'reservations.id', '=', 'payment_invoices.reservation_id')
            ->join('venues', 'venues.id', '=', 'reservations.venue_id')
            ->where('payment_invoices.status', 'paid')
            ->where('venues.owner_id', $user->id)
            ->sum('payment_invoices.amount');

        $classRevenue = (float) EducationalClassEnrollment::query()
            ->join('educational_classes', 'educational_classes.id', '=', 'educational_class_enrollments.educational_class_id')
            ->where('educational_classes.instructor_id', $user->id)
            ->where('educational_class_enrollments.status', 'registered')
            ->where('educational_class_enrollments.payment_status', 'paid')
            ->sum('educational_class_enrollments.price');

        $reserved = (float) WithdrawalRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved', 'paid'])->sum('amount');

        return [
            'venue_revenue' => $venueRevenue,
            'class_revenue' => $classRevenue,
            'total_revenue' => $venueRevenue + $classRevenue,
            'reserved_or_withdrawn' => $reserved,
            'available_to_withdraw' => max(0, $venueRevenue + $classRevenue - $reserved),
        ];
    }
}
