<?php

namespace App\Http\Controllers\Webhook;

use App\Enums\JobStatus;
use App\Enums\LedgerAccountType;
use App\Enums\LedgerEntryType;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\MarketplaceJob;
use App\Services\JobPostingFeeService;
use App\Services\JobWorkflowService;
use App\Services\LedgerService;
use App\Services\NotificationService;
use App\Services\WiPayGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WiPayWebhookController extends Controller
{
    public function __construct(
        private readonly WiPayGateway $gateway,
        private readonly JobWorkflowService $workflow,
        private readonly JobPostingFeeService $postingFee,
        private readonly LedgerService $ledger,
        private readonly NotificationService $notifications,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $payload = $request->all();
        $headers = collect($request->headers->all())
            ->map(fn (array $values) => $values[0] ?? '')
            ->all();

        try {
            $result = $this->gateway->handleWebhook($payload, $headers, $rawBody);
        } catch (\Throwable $exception) {
            Log::error('WiPay webhook processing failed', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => $exception->getMessage()], 400);
        }

        if ($result['duplicate'] ?? false) {
            return response()->json(['status' => 'duplicate', 'event_id' => $result['event_id']]);
        }

        $payment = $result['payment'] ?? null;

        if ($payment !== null && $payment->status === PaymentStatus::Succeeded) {
            if ($payment->payment_type === 'job_posting_fee' && $payment->marketplace_job_id !== null) {
                $this->handleSuccessfulPostingFee($payment->marketplaceJob, $payment);
            } elseif ($payment->marketplace_job_id !== null) {
                $this->handleSuccessfulJobFunding($payment->marketplaceJob, $payment);
            }
        }

        return response()->json([
            'status' => $result['status'],
            'event_id' => $result['event_id'],
        ]);
    }

    private function handleSuccessfulPostingFee(MarketplaceJob $job, \App\Models\Payment $payment): void
    {
        if ($job->posting_fee_paid_at !== null) {
            return;
        }

        $this->postingFee->markPaid($job, $payment->id);

        if ($job->status === JobStatus::PendingPostingFee) {
            $this->workflow->postingFeePaid($job, $payment->payer);

            if ($payment->payer !== null) {
                $this->notifications->notify(
                    $payment->payer,
                    'job.posting_fee_paid',
                    'Job posting fee confirmed',
                    "Your job \"{$job->title}\" is ready for funding.",
                    ['marketplace_job_id' => $job->id],
                );
            }
        }
    }

    private function handleSuccessfulJobFunding(MarketplaceJob $job, \App\Models\Payment $payment): void
    {
        if ($job->status === JobStatus::PendingPayment) {
            $this->workflow->markPaid($job, $payment->payer);

            $clientAccount = $this->ledger->createAccount(
                $payment->payer,
                LedgerAccountType::ClientWallet,
                $job->currency,
            );
            $escrowAccount = $this->ledger->createAccount(null, LedgerAccountType::Escrow, $job->currency);

            $this->ledger->transfer(
                from: $clientAccount,
                to: $escrowAccount,
                amountMinor: (int) $payment->net_amount_minor,
                debitType: LedgerEntryType::EscrowHold,
                creditType: LedgerEntryType::EscrowHold,
                reference: $payment,
                idempotencyKey: "wipay-webhook:{$payment->id}",
                metadata: ['marketplace_job_id' => $job->id],
            );

            if ($payment->payer !== null) {
                $this->notifications->notify(
                    $payment->payer,
                    'job.funded',
                    'Job funded',
                    "Payment confirmed for job \"{$job->title}\".",
                    ['marketplace_job_id' => $job->id, 'payment_id' => $payment->id],
                );
            }
        }
    }
}
