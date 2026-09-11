<?php

namespace App\Services;

use App\Models\MarketplaceJob;
use App\Models\User;

class JobPostingFeeService
{
    public function __construct(
        private readonly PlatformSettingsService $settings,
        private readonly WiPayGateway $gateway,
        private readonly AuditLogService $auditLog,
    ) {}

    public function feeAmountMinor(): int
    {
        $amount = (float) $this->settings->get('job_posting_fee', 20);

        return (int) round($amount * 100);
    }

    public function feeCurrency(): string
    {
        return strtoupper((string) $this->settings->get('job_posting_fee_currency', 'TTD'));
    }

    public function requiresPayment(MarketplaceJob $job): bool
    {
        return $job->posting_fee_paid_at === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function initiatePayment(MarketplaceJob $job, User $user, string $idempotencyKey): array
    {
        $amountMinor = $this->feeAmountMinor();
        $currency = $this->feeCurrency();

        $result = $this->gateway->initiatePayment(
            amountMinor: $amountMinor,
            currency: $currency,
            description: "Job posting fee: {$job->title}",
            idempotencyKey: $idempotencyKey,
            metadata: [
                'payer_id' => $user->id,
                'marketplace_job_id' => $job->id,
                'payment_type' => 'job_posting_fee',
            ],
        );

        $job->update([
            'posting_fee_amount_minor' => $amountMinor,
            'posting_fee_currency' => $currency,
        ]);

        $this->auditLog->log(
            'job.posting_fee_initiated',
            $job,
            newValues: [
                'amount_minor' => $amountMinor,
                'currency' => $currency,
                'payment_id' => $result['payment']?->id,
            ],
            user: $user,
        );

        return $result;
    }

    public function markPaid(MarketplaceJob $job, int $paymentId): MarketplaceJob
    {
        $job->update([
            'posting_fee_paid_at' => now(),
            'posting_fee_payment_id' => $paymentId,
        ]);

        $this->auditLog->log(
            'job.posting_fee_paid',
            $job,
            newValues: ['payment_id' => $paymentId],
        );

        return $job->fresh();
    }
}
