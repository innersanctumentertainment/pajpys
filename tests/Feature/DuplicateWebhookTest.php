<?php

namespace Tests\Feature;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentGatewayEvent;
use App\Models\User;
use App\Services\WiPayGateway;
use Tests\MarketplaceTestCase;

class DuplicateWebhookTest extends MarketplaceTestCase
{
    public function test_scenario_ten_duplicate_webhook_is_idempotent(): void
    {
        config([
            'wipay.account_number' => 'test-account',
            'wipay.api_key' => 'test-key',
            'wipay.base_url' => 'https://wipay.test',
            'wipay.webhook_secret' => 'webhook-secret',
        ]);

        $user = User::factory()->create();
        $user->assignRole('client');

        $payment = Payment::query()->create([
            'payer_id' => $user->id,
            'payment_type' => 'gateway',
            'status' => PaymentStatus::Processing,
            'amount' => 100,
            'amount_minor' => 10000,
            'net_amount' => 100,
            'net_amount_minor' => 10000,
            'currency' => 'TTD',
            'gateway' => 'wipay',
            'idempotency_key' => 'pay-test-1',
            'gateway_reference' => 'gw-ref-123',
        ]);

        $payload = [
            'id' => 'evt_duplicate_1',
            'event' => 'payment.success',
            'data' => [
                'id' => 'gw-ref-123',
                'data' => json_encode(['payment_id' => $payment->id]),
            ],
        ];

        $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $rawBody, 'webhook-secret');
        $headers = [
            'X-WiPay-Webhook-Id' => 'evt_duplicate_1',
            'X-WiPay-Webhook-Event' => 'payment.success',
            'X-WiPay-Webhook-Signature' => $signature,
        ];

        /** @var WiPayGateway $gateway */
        $gateway = app(PaymentGatewayInterface::class);

        $first = $gateway->handleWebhook($payload, $headers, $rawBody);
        $second = $gateway->handleWebhook($payload, $headers, $rawBody);

        $this->assertSame('processed', $first['status']);
        $this->assertFalse($first['duplicate']);
        $this->assertSame('duplicate', $second['status']);
        $this->assertTrue($second['duplicate']);
        $this->assertSame(1, PaymentGatewayEvent::query()->count());
        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
    }
}
