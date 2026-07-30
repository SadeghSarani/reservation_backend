<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Models\PaymentInvoice;

interface PaymentGateway
{
    public function request(PaymentInvoice $invoice): array;

    public function verify(PaymentInvoice $invoice, array $payload): array;
}
