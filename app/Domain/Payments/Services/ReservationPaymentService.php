<?php

namespace App\Domain\Payments\Services;

use App\Domain\Payments\Gateways\PaymentGateway;
use App\Domain\Payments\Models\PaymentInvoice;
use App\Models\Reservation;
use App\Models\VenueTimePrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ReservationPaymentService
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function initiate(int $userId, int $intervalId, array $additionals = []): array
    {
        $invoice = DB::transaction(function () use ($userId, $intervalId, $additionals) {
            $interval = VenueTimePrice::with(['calendar', 'venue'])->lockForUpdate()->findOrFail($intervalId);

            if (! $interval->calendar) {
                throw new RuntimeException('Calendar for this interval was not found.');
            }

            if (Reservation::where('calendar_interval_id', $interval->id)->exists()) {
                throw new RuntimeException('Slot already reserved.');
            }

            $hasActivePayment = PaymentInvoice::where('calendar_interval_id', $interval->id)
                ->where('status', 'pending')->where('expires_at', '>', now())->exists();
            if ($hasActivePayment) {
                throw new RuntimeException('A payment is already in progress for this slot.');
            }

            $availableAdditionals = collect($interval->venue->additionals ?? [])->keyBy('name');
            $additionals = collect($additionals)->map(function ($item) use ($availableAdditionals) {
                $additional = $availableAdditionals->get($item['name']);
                if (! is_array($additional) || ! isset($additional['price'])) {
                    throw new RuntimeException('One of the selected additionals is invalid.');
                }

                return $additional;
            })->values()->all();
            $additionalsPrice = collect($additionals)->sum(fn ($item) => (float) $item['price']);
            $date = $interval->calendar->day;

            return PaymentInvoice::create([
                'number' => (string) Str::uuid(),
                'user_id' => $userId,
                'calendar_interval_id' => $interval->id,
                'amount' => (float) $interval->price + $additionalsPrice,
                'status' => 'pending',
                'expires_at' => now()->addMinutes((int) config('services.boometo.invoice_ttl', 20)),
                'reservation_data' => [
                    'user_id' => $userId,
                    'venue_id' => $interval->venue_id,
                    'calendar_interval_id' => $interval->id,
                    'start_at' => $date->format('Y-m-d').' '.$interval->start_time,
                    'end_at' => $date->format('Y-m-d').' '.$interval->end_time,
                    'total_price' => (float) $interval->price + $additionalsPrice,
                    'additionals' => $additionals,
                    'status' => 'confirmed',
                ],
            ]);
        });

        try {
            return ['invoice' => $invoice, ...$this->gateway->request($invoice)];
        } catch (\Throwable $exception) {
            $invoice->update(['status' => 'failed']);
            throw $exception;
        }
    }

    public function complete(PaymentInvoice $invoice, array $payload): PaymentInvoice
    {
        if ($invoice->status === 'paid') {
            return $invoice->load('reservation');
        }

        if ($invoice->status !== 'pending') {
            throw new RuntimeException('This invoice can no longer be paid.');
        }

        $verification = $this->gateway->verify($invoice, $payload);
        if (! $verification['successful']) {
            throw new RuntimeException('Payment verification failed.');
        }

        return DB::transaction(function () use ($invoice, $verification) {
            $invoice = PaymentInvoice::lockForUpdate()->findOrFail($invoice->id);
            if ($invoice->status === 'paid') {
                return $invoice->load('reservation');
            }

            VenueTimePrice::lockForUpdate()->findOrFail($invoice->calendar_interval_id);
            if (Reservation::where('calendar_interval_id', $invoice->calendar_interval_id)->exists()) {
                // The provider confirmed the charge; keep it paid so it can be refunded manually.
                $invoice->update(['status' => 'paid', 'reference' => $verification['reference'], 'paid_at' => now()]);
                throw new RuntimeException('Payment succeeded, but this slot is no longer available. Please refund this invoice.');
            }

            $reservation = Reservation::create($invoice->reservation_data);

            $invoice->update([
                'status' => 'paid',
                'reference' => $verification['reference'],
                'paid_at' => now(),
                'reservation_id' => $reservation->id,
            ]);

            return $invoice->fresh()->load('reservation');
        });
    }
}
