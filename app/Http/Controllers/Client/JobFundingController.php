<?php

namespace App\Http\Controllers\Client;

use App\Enums\JobStatus;
use App\Enums\LedgerAccountType;
use App\Enums\LedgerEntryType;
use App\Enums\PaymentStatus;
use App\Http\Concerns\AuthorizesMarketplaceJob;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\InitiateJobFundingRequest;
use App\Models\MarketplaceJob;
use App\Services\AuditLogService;
use App\Services\JobWorkflowService;
use App\Services\LedgerService;
use App\Services\PlatformSettingsService;
use App\Services\WiPayGateway;
use Illuminate\Http\JsonResponse;

class JobFundingController extends Controller
{
    use AuthorizesMarketplaceJob;

    public function __construct(
        private readonly WiPayGateway $gateway,
        private readonly JobWorkflowService $workflow,
        private readonly LedgerService $ledger,
        private readonly AuditLogService $auditLog,
        private readonly PlatformSettingsService $settings,
    ) {}

    public function initiate(InitiateJobFundingRequest $request, MarketplaceJob $job): JsonResponse
    {
        $user = $request->user();
        $this->authorizeClientJob($user, $job);

        if ($job->status !== JobStatus::PendingPayment) {
            return response()->json(['message' => 'Job posting fee must be paid before funding the job budget.'], 422);
        }

        if ($job->posting_fee_paid_at === null) {
            return response()->json(['message' => 'Job posting fee has not been paid.'], 422);
        }

        if ($job->budget_amount_minor === null || $job->budget_amount_minor <= 0) {
            return response()->json(['message' => 'Job budget must be set before funding.'], 422);
        }

        $feePercent = (float) $this->settings->get('platform_fee_percentage', 10);
        $feeMinor = (int) round($job->budget_amount_minor * ($feePercent / 100));
        $totalMinor = $job->budget_amount_minor + $feeMinor;

        $idempotencyKey = $request->validated('idempotency_key');

        $result = $this->gateway->initiatePayment(
            amountMinor: $totalMinor,
            currency: $job->currency,
            description: "Fund job: {$job->title}",
            idempotencyKey: $idempotencyKey,
            metadata: [
                'payer_id' => $user->id,
                'marketplace_job_id' => $job->id,
                'payment_type' => 'job_funding',
                'fee_amount_minor' => $feeMinor,
                'budget_amount_minor' => $job->budget_amount_minor,
            ],
        );

        $this->auditLog->log(
            'job.funding_initiated',
            $job,
            newValues: [
                'payment_id' => $result['payment']?->id,
                'amount_minor' => $totalMinor,
                'status' => $result['status'],
            ],
            user: $user,
        );

        return response()->json($result);
    }

    public function confirm(MarketplaceJob $job): JsonResponse
    {
        $user = request()->user();
        $this->authorizeClientJob($user, $job);

        $payment = $job->payments()
            ->where('status', PaymentStatus::Succeeded)
            ->latest()
            ->first();

        if ($payment === null) {
            return response()->json(['message' => 'No successful payment found for this job.'], 422);
        }

        if ($job->status === JobStatus::PendingPayment) {
            $this->workflow->markPaid($job, $user);

            $clientAccount = $this->ledger->createAccount($user, LedgerAccountType::ClientWallet, $job->currency);
            $escrowAccount = $this->ledger->createAccount(null, LedgerAccountType::Escrow, $job->currency);

            $this->ledger->transfer(
                from: $clientAccount,
                to: $escrowAccount,
                amountMinor: (int) $payment->net_amount_minor,
                debitType: LedgerEntryType::EscrowHold,
                creditType: LedgerEntryType::EscrowHold,
                reference: $payment,
                idempotencyKey: "job-funding:{$payment->id}",
                metadata: ['marketplace_job_id' => $job->id],
            );
        }

        return response()->json(['job' => $job->fresh(), 'payment' => $payment]);
    }
}
