<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Models\PaymentInvoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class BoometoGateway implements PaymentGateway
{
    public function request(PaymentInvoice $invoice): array
    {
        try {
            $response = Http::withToken($this->token())
                ->acceptJson()->asJson()->timeout(20)
                ->post($this->url().'/transaction', [
                    'invoice_id' => $invoice->id,
                    'amount' => (int) round((float) $invoice->amount * (int) config('services.boometo.amount_multiplier', 10)),
                    'callback' => route('payments.boometo.callback', ['invoice' => $invoice->number]),
                ]);

            $html = trim($response->body());
            if (! $response->successful() || $html === '') {
                Log::warning('Boometo request failed', ['invoice' => $invoice->number, 'status' => $response->status(), 'body' => $response->body()]);
                $body = str($response->body())->squish()->limit(500)->toString();
                throw new RuntimeException(sprintf('Boometo payment request failed with HTTP %d%s', $response->status(), $body !== '' ? ': '.$body : '.'));
            }

            $metadata = $invoice->metadata ?? [];
            $metadata['boometo_redirection_html'] = $html;
            $invoice->update(['metadata' => $metadata]);

            return [
                'authority' => $invoice->number,
                'payment_url' => route('payments.boometo.redirect', $invoice->number),
            ];
        } catch (Throwable $exception) {
            Log::error('Boometo request error', ['invoice' => $invoice->number, 'message' => $exception->getMessage()]);
            throw $exception;
        }
    }

    public function verify(PaymentInvoice $invoice, array $payload): array
    {
        try {
            $response = Http::withToken($this->token())
                ->acceptJson()->asJson()->timeout(20)
                ->post($this->url().'/transaction/status', ['invoice_id' => $invoice->id]);

            if ($response->status() === 200) {
                $data = $response->json();
                $reference = is_array($data) ? ($data['trasn_num'] ?? $data['trans_num'] ?? null) : null;

                return ['successful' => true, 'reference' => $reference === null ? null : (string) $reference];
            }

            Log::warning('Boometo verify failed', ['invoice' => $invoice->number, 'status' => $response->status(), 'body' => $response->body()]);
        } catch (Throwable $exception) {
            Log::error('Boometo verify error', ['invoice' => $invoice->number, 'message' => $exception->getMessage()]);
        }

        return ['successful' => false, 'reference' => null];
    }

    private function url(): string
    {
        $url = rtrim((string) config('services.boometo.url'), '/');
        throw_if($url === '', RuntimeException::class, 'BOOMETO_URL is not configured.');

        return $url;
    }

    private function token(): string
    {
        $token = (string) config('services.boometo.token');
        throw_if($token === '', RuntimeException::class, 'BOOMETO_TOKEN is not configured.');

        return $token;
    }
}
