<?php

namespace App\Contracts;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * @param  array<string, mixed>  $metadata
     * @return array{
     *     status: string,
     *     payment: Payment|null,
     *     redirect_url: string|null,
     *     message: string|null,
     *     configured: bool
     * }
     */
    public function initiatePayment(
        int $amountMinor,
        string $currency,
        string $description,
        string $idempotencyKey,
        array $metadata = [],
    ): array;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array{
     *     status: string,
     *     event_id: string|null,
     *     payment: Payment|null,
     *     message: string|null,
     *     duplicate: bool
     * }
     */
    public function handleWebhook(array $payload, array $headers, string $rawBody): array;

    public function isConfigured(): bool;
}
