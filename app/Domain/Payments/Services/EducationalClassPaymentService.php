<?php

namespace App\Domain\Payments\Services;

use App\Domain\Payments\Gateways\PaymentGateway;
use App\Domain\Payments\Models\PaymentInvoice;
use App\Models\EducationalClass;
use App\Models\EducationalClassEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EducationalClassPaymentService
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function initiate(int $userId, EducationalClass $educationalClass): array
    {
        $invoice = DB::transaction(function () use ($userId, $educationalClass) {
            $class = EducationalClass::lockForUpdate()->findOrFail($educationalClass->id);
            throw_if($class->status !== 'published', RuntimeException::class, 'This class is not open for registration.');
            throw_if($class->registration_deadline && $class->registration_deadline->isPast(), RuntimeException::class, 'Registration deadline has passed.');
            throw_if($class->starts_on->isPast(), RuntimeException::class, 'This class has already started.');

            $existing = EducationalClassEnrollment::where('educational_class_id', $class->id)
                ->where('user_id', $userId)->first();
            throw_if($existing?->payment_status === 'paid' && $existing->status === 'registered', RuntimeException::class, 'You are already registered in this class.');

            $hasPendingPayment = PaymentInvoice::where('purpose', 'educational_class')
                ->where('educational_class_id', $class->id)->where('user_id', $userId)
                ->where('status', 'pending')->where('expires_at', '>', now())->exists();
            throw_if($hasPendingPayment, RuntimeException::class, 'You already have a pending payment for this class.');

            $registeredByOthers = $class->activeEnrollments()->where('user_id', '!=', $userId)->count();
            $activePaymentHolds = PaymentInvoice::where('purpose', 'educational_class')
                ->where('educational_class_id', $class->id)->where('user_id', '!=', $userId)
                ->where('status', 'pending')->where('expires_at', '>', now())->count();
            throw_if($registeredByOthers + $activePaymentHolds >= $class->capacity, RuntimeException::class, 'This class is full.');

            return PaymentInvoice::create([
                'number' => (string) Str::uuid(),
                'user_id' => $userId,
                'educational_class_id' => $class->id,
                'purpose' => 'educational_class',
                'amount' => $class->price,
                'status' => 'pending',
                'expires_at' => now()->addMinutes((int) config('services.boometo.invoice_ttl', 20)),
                'metadata' => ['class_slug' => $class->slug, 'class_title' => $class->title],
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
            return $invoice->load(['enrollment', 'educationalClass']);
        }
        throw_if($invoice->status !== 'pending', RuntimeException::class, 'This invoice can no longer be paid.');

        $verification = $this->gateway->verify($invoice, $payload);
        throw_if(! $verification['successful'], RuntimeException::class, 'Payment verification failed.');

        return DB::transaction(function () use ($invoice, $verification) {
            $invoice = PaymentInvoice::lockForUpdate()->findOrFail($invoice->id);
            if ($invoice->status === 'paid') {
                return $invoice->load(['enrollment', 'educationalClass']);
            }

            $class = EducationalClass::lockForUpdate()->findOrFail($invoice->educational_class_id);
            $existing = EducationalClassEnrollment::where('educational_class_id', $class->id)
                ->where('user_id', $invoice->user_id)->first();
            $registeredByOthers = $class->activeEnrollments()->where('user_id', '!=', $invoice->user_id)->count();
            if ($registeredByOthers >= $class->capacity) {
                $invoice->update(['status' => 'paid', 'reference' => $verification['reference'], 'paid_at' => now()]);
                throw new RuntimeException('Payment succeeded, but the class is full. Please refund this invoice.');
            }

            $enrollment = EducationalClassEnrollment::updateOrCreate(
                ['educational_class_id' => $class->id, 'user_id' => $invoice->user_id],
                [
                    'price' => $invoice->amount, 'status' => 'registered', 'payment_status' => 'paid',
                    'registered_at' => now(), 'cancelled_at' => null,
                ],
            );
            $invoice->update([
                'status' => 'paid', 'reference' => $verification['reference'], 'paid_at' => now(),
                'enrollment_id' => $enrollment->id,
            ]);

            return $invoice->fresh()->load(['enrollment', 'educationalClass']);
        });
    }
}
