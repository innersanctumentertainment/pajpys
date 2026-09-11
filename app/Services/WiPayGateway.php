<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentGatewayEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WiPayGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function isConfigured(): bool
    {
        return filled(config('wipay.account_number'))
            && filled(config('wipay.api_key'))
            && filled(config('wipay.base_url'));
    }

    public function initiatePayment(
        int $amountMinor,
        string $currency,
        string $description,
        string $idempotencyKey,
        array $metadata = [],
    ): array {
        $existing = Payment::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing !== null) {
            return $this->buildInitiateResponse($existing);
        }

        $payerId = $metadata['payer_id'] ?? $metadata['user_id'] ?? null;

        if ($payerId === null) {
            throw new RuntimeException('payer_id is required in payment metadata.');
        }

        $majorAmount = $amountMinor / 100;
        $feeMinor = (int) ($metadata['fee_amount_minor'] ?? 0);

        $payment = Payment::query()->create([
            'payer_id' => $payerId,
            'payee_id' => $metadata['payee_id'] ?? null,
            'marketplace_job_id' => $metadata['marketplace_job_id'] ?? null,
            'wallet_id' => $metadata['wallet_id'] ?? null,
            'payment_type' => $metadata['payment_type'] ?? 'gateway',
            'status' => PaymentStatus::Pending,
            'amount' => $majorAmount,
            'amount_minor' => $amountMinor,
            'currency' => strtoupper($currency),
            'fee_amount' => $feeMinor / 100,
            'fee_amount_minor' => $feeMinor,
            'net_amount' => ($amountMinor - $feeMinor) / 100,
            'net_amount_minor' => $amountMinor - $feeMinor,
            'gateway' => 'wipay',
            'idempotency_key' => $idempotencyKey,
        ]);

        if (! $this->isConfigured()) {
            PaymentAttempt::query()->create([
                'payment_id' => $payment->id,
                'attempt_number' => 1,
                'status' => PaymentStatus::Pending,
                'gateway' => 'wipay',
                'failure_reason' => 'WiPay credentials are not configured.',
                'response_payload' => ['configured' => false],
                'attempted_at' => now(),
            ]);

            return [
                'status' => 'pending_configuration',
                'payment' => $payment->fresh(),
                'redirect_url' => null,
                'message' => 'Payment gateway is not configured. Payment saved in pending state.',
                'configured' => false,
            ];
        }

        $passThroughData = json_encode([
            'payment_id' => $payment->id,
            'idempotency_key' => $idempotencyKey,
            'metadata' => $metadata,
        ], JSON_THROW_ON_ERROR);

        try {
            $response = Http::timeout((int) config('wipay.timeout'))
                ->withHeaders([
                    'Authorization' => 'Bearer '.config('wipay.api_key'),
                    'Accept' => 'application/json',
                    'Idempotency-Key' => $idempotencyKey,
                ])
                ->post($this->endpoint('/v1/payments'), [
                    'account_number' => config('wipay.account_number'),
                    'amount' => $amountMinor,
                    'currency' => strtoupper($currency),
                    'description' => $description,
                    'response_url' => config('wipay.response_url'),
                    'webhook_url' => config('wipay.webhook_url'),
                    'data' => $passThroughData,
                ]);

            $body = $response->json() ?? [];

            if (! $response->successful()) {
                PaymentAttempt::query()->create([
                    'payment_id' => $payment->id,
                    'attempt_number' => 1,
                    'status' => PaymentStatus::Failed,
                    'gateway' => 'wipay',
                    'failure_reason' => $body['message'] ?? 'WiPay payment initiation failed.',
                    'request_payload' => ['description' => $description],
                    'response_payload' => $body,
                    'attempted_at' => now(),
                ]);

                $payment->update(['status' => PaymentStatus::Failed]);

                return [
                    'status' => 'failed',
                    'payment' => $payment->fresh(),
                    'redirect_url' => null,
                    'message' => $body['message'] ?? 'WiPay payment initiation failed.',
                    'configured' => true,
                ];
            }

            $redirectUrl = $body['redirect_url'] ?? $body['payment_url'] ?? null;
            $externalId = $body['id'] ?? $body['transaction_id'] ?? null;

            PaymentAttempt::query()->create([
                'payment_id' => $payment->id,
                'attempt_number' => 1,
                'status' => PaymentStatus::Processing,
                'gateway' => 'wipay',
                'gateway_reference' => $externalId,
                'request_payload' => ['description' => $description],
                'response_payload' => $body,
                'attempted_at' => now(),
            ]);

            $payment->update([
                'status' => PaymentStatus::Processing,
                'gateway_reference' => $externalId,
            ]);

            return [
                'status' => 'processing',
                'payment' => $payment->fresh(),
                'redirect_url' => $redirectUrl,
                'message' => null,
                'configured' => true,
            ];
        } catch (\Throwable $exception) {
            Log::error('WiPay initiatePayment failed', [
                'payment_id' => $payment->id,
                'error' => $exception->getMessage(),
            ]);

            PaymentAttempt::query()->create([
                'payment_id' => $payment->id,
                'attempt_number' => 1,
                'status' => PaymentStatus::Pending,
                'gateway' => 'wipay',
                'failure_reason' => $exception->getMessage(),
                'response_payload' => ['error' => $exception->getMessage()],
                'attempted_at' => now(),
            ]);

            return [
                'status' => 'pending',
                'payment' => $payment->fresh(),
                'redirect_url' => null,
                'message' => 'Unable to reach WiPay. Payment remains pending.',
                'configured' => true,
            ];
        }
    }

    public function handleWebhook(array $payload, array $headers, string $rawBody): array
    {
        if (! $this->isConfigured()) {
            return [
                'status' => 'ignored',
                'event_id' => null,
                'payment' => null,
                'message' => 'WiPay webhook received but gateway is not configured.',
                'duplicate' => false,
            ];
        }

        $eventId = $headers['X-WiPay-Webhook-Id']
            ?? $headers['x-wipay-webhook-id']
            ?? ($payload['id'] ?? null);

        if ($eventId === null) {
            throw new RuntimeException('WiPay webhook missing event id.');
        }

        $existingEvent = PaymentGatewayEvent::query()
            ->where('gateway', 'wipay')
            ->where('gateway_event_id', $eventId)
            ->first();

        if ($existingEvent !== null) {
            return [
                'status' => 'duplicate',
                'event_id' => $eventId,
                'payment' => $existingEvent->payment,
                'message' => 'Webhook already processed.',
                'duplicate' => true,
            ];
        }

        if (! $this->verifySignature($headers, $rawBody)) {
            throw new RuntimeException('Invalid WiPay webhook signature.');
        }

        $eventType = $headers['X-WiPay-Webhook-Event']
            ?? $headers['x-wipay-webhook-event']
            ?? ($payload['event'] ?? null);

        $payment = $this->resolvePaymentFromPayload($payload);

        $gatewayEvent = PaymentGatewayEvent::query()->create([
            'payment_id' => $payment?->id,
            'gateway' => 'wipay',
            'event_type' => (string) $eventType,
            'gateway_event_id' => $eventId,
            'payload' => $payload,
            'processing_status' => 'processing',
            'received_at' => now(),
        ]);

        if ($payment !== null) {
            $status = $this->mapEventToStatus($eventType, $payload);

            $payment->update([
                'status' => $status,
                'paid_at' => $status === PaymentStatus::Succeeded ? now() : $payment->paid_at,
            ]);

            $this->auditLog->log(
                event: 'wipay.webhook_processed',
                auditable: $payment,
                newValues: [
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                    'status' => $status->value,
                ],
            );
        }

        $gatewayEvent->update(['processing_status' => 'processed']);

        return [
            'status' => 'processed',
            'event_id' => $eventId,
            'payment' => $payment?->fresh(),
            'message' => null,
            'duplicate' => false,
        ];
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function verifySignature(array $headers, string $rawBody): bool
    {
        $secret = config('wipay.webhook_secret');

        if (! filled($secret)) {
            return false;
        }

        $signature = $headers['X-WiPay-Webhook-Signature']
            ?? $headers['x-wipay-webhook-signature']
            ?? '';

        if ($signature === '') {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }

    private function mapEventToStatus(?string $eventType, array $payload): PaymentStatus
    {
        return match ($eventType) {
            'payment.success' => PaymentStatus::Succeeded,
            'payment.failed', 'payment.error' => PaymentStatus::Failed,
            'payment.refunded' => PaymentStatus::Refunded,
            'payment.refund_requested' => PaymentStatus::Processing,
            default => PaymentStatus::tryFrom($payload['data']['status'] ?? '') ?? PaymentStatus::Processing,
        };
    }

    private function resolvePaymentFromPayload(array $payload): ?Payment
    {
        $data = $payload['data'] ?? [];
        $passThrough = [];

        if (isset($data['data']) && is_string($data['data'])) {
            $passThrough = json_decode($data['data'], true) ?? [];
        } elseif (isset($data['data']) && is_array($data['data'])) {
            $passThrough = $data['data'];
        }

        if (isset($passThrough['payment_id'])) {
            return Payment::query()->find($passThrough['payment_id']);
        }

        if (isset($passThrough['idempotency_key'])) {
            return Payment::query()
                ->where('idempotency_key', $passThrough['idempotency_key'])
                ->first();
        }

        $externalId = $data['id'] ?? $data['transaction_id'] ?? null;

        if ($externalId !== null) {
            return Payment::query()
                ->where('gateway_reference', $externalId)
                ->first();
        }

        return null;
    }

    private function buildInitiateResponse(Payment $payment): array
    {
        $configured = $this->isConfigured();
        $latestAttempt = $payment->attempts()->latest('id')->first();
        $attemptPayload = $latestAttempt?->response_payload ?? [];

        if (! $configured || (($attemptPayload['configured'] ?? true) === false)) {
            $status = 'pending_configuration';
        } elseif ($payment->status === PaymentStatus::Processing) {
            $status = 'processing';
        } elseif ($payment->status === PaymentStatus::Failed) {
            $status = 'failed';
        } else {
            $status = 'pending';
        }

        return [
            'status' => $status,
            'payment' => $payment,
            'redirect_url' => $attemptPayload['redirect_url'] ?? $attemptPayload['payment_url'] ?? null,
            'message' => $latestAttempt?->failure_reason,
            'configured' => $configured,
        ];
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('wipay.base_url'), '/').'/'.ltrim($path, '/');
    }
}
