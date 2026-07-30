<?php

namespace App\Http\Controllers;

use App\Domain\Payments\Models\PaymentInvoice;
use App\Domain\Payments\Services\EducationalClassPaymentService;
use App\Domain\Payments\Services\ReservationPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use RuntimeException;

class PaymentController extends Controller
{
    public function redirect(PaymentInvoice $invoice)
    {
        abort_unless($invoice->status === 'pending', 410, 'This payment is no longer active.');
        $html = data_get($invoice->metadata, 'boometo_redirection_html');
        abort_if(! is_string($html) || trim($html) === '', 404, 'Payment page was not found.');

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function callback(
        Request $request,
        ReservationPaymentService $reservationPayments,
        EducationalClassPaymentService $classPayments,
    ) {
        $number = (string) $request->query('invoice');
        $invoice = PaymentInvoice::where('number', $number)->firstOrFail();

        try {
            $invoice = $invoice->purpose === 'educational_class'
                ? $classPayments->complete($invoice, $request->all())
                : $reservationPayments->complete($invoice, $request->all());
        } catch (RuntimeException $exception) {
            return $this->frontendRedirect([
                'status' => 'false',
                'invoice' => $invoice->number,
                'message' => $exception->getMessage(),
            ]);
        }

        $result = [
            'status' => 'true',
            'invoice' => $invoice->number,
            'reference' => $invoice->reference,
        ];
        if ($invoice->purpose === 'educational_class') {
            $result['type'] = 'educational_class';
            $result['enrollment_id'] = $invoice->enrollment_id;
            $result['class'] = $invoice->educationalClass->slug;
        } else {
            $result['type'] = 'reservation';
            $result['reservation_id'] = $invoice->reservation_id;
        }

        return $this->frontendRedirect($result);
    }

    private function frontendRedirect(array $parameters)
    {
        $url = trim((string) config('services.boometo.frontend_callback_url'));
        abort_if($url === '', 500, 'Frontend payment callback URL is not configured.');
        $separator = str_contains($url, '?') ? '&' : '?';

        return redirect()->away($url.$separator.Arr::query($parameters));
    }
}
