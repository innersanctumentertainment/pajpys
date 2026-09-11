<?php

namespace App\Contracts;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    public function initiatePayment(int $amountMinor, string $currency, string $description, string $idempotencyKey, array $metadata = []): array;
    public function handleWebhook(array $payload, array $headers, string $rawBody): array;
    public function isConfigured(): bool;
}
