<?php

namespace Tests\Support;

use App\Enums\JobStatus;
use App\Enums\LedgerAccountType;
use App\Enums\LedgerEntryType;
use App\Models\Category;
use App\Models\JobAgreement;
use App\Models\JobCandidate;
use App\Models\MarketplaceJob;
use App\Models\User;
use App\Services\JobWorkflowService;
use App\Services\LedgerService;
use Illuminate\Support\Str;

class MarketplaceScenarioBuilder
{
    public function __construct(
        private readonly JobWorkflowService $workflow,
        private readonly LedgerService $ledger,
    ) {}

    public function createClient(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'email_verified' => true,
            'email_verified_at' => now(),
        ], $attributes));

        $user->assignRole('client');

        return $user;
    }

    public function createVa(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'email_verified' => true,
            'email_verified_at' => now(),
        ], $attributes));

        $user->assignRole('va');

        $user->vaProfile()->create([
            'display_name' => $user->name,
            'status' => 'approved',
            'availability_status' => 'available',
            'is_verified' => true,
            'approved_at' => now(),
        ]);

        return $user;
    }

    public function createJob(User $client, int $budgetMinor = 10000, string $currency = 'TTD'): MarketplaceJob
    {
        $category = Category::query()->first();

        return MarketplaceJob::query()->create([
            'client_id' => $client->id,
            'category_id' => $category?->id,
            'title' => 'Test Job '.Str::random(6),
            'slug' => 'test-job-'.Str::random(8),
            'description' => 'Test job description',
            'status' => JobStatus::Draft,
            'budget_amount' => $budgetMinor / 100,
            'budget_amount_minor' => $budgetMinor,
            'currency' => $currency,
        ]);
    }

    public function fundClientWallet(User $client, int $amountMinor, string $currency = 'TTD'): void
    {
        $account = $this->ledger->createAccount($client, LedgerAccountType::ClientWallet, $currency);

        $this->ledger->postEntry(
            $account,
            $amountMinor,
            $currency,
            LedgerEntryType::Credit,
            null,
            'fund:client:'.$client->id.':'.uniqid('', true),
        );
    }

    public function fundVaWallet(User $va, int $amountMinor, string $currency = 'TTD'): void
    {
        $account = $this->ledger->createAccount($va, LedgerAccountType::VaWallet, $currency);

        $this->ledger->postEntry(
            $account,
            $amountMinor,
            $currency,
            LedgerEntryType::Credit,
            null,
            'fund:va:'.$va->id.':'.uniqid('', true),
        );
    }

    public function applyAsCandidate(MarketplaceJob $job, User $va, int $proposedMinor = 10000): JobCandidate
    {
        return JobCandidate::query()->create([
            'marketplace_job_id' => $job->id,
            'va_id' => $va->id,
            'status' => 'applied',
            'proposed_amount' => $proposedMinor / 100,
            'proposed_amount_minor' => $proposedMinor,
            'currency' => $job->currency,
        ]);
    }

    public function createAgreement(MarketplaceJob $job, User $va, int $amountMinor): JobAgreement
    {
        return JobAgreement::query()->create([
            'marketplace_job_id' => $job->id,
            'client_id' => $job->client_id,
            'va_id' => $va->id,
            'status' => 'active',
            'agreed_amount' => $amountMinor / 100,
            'agreed_amount_minor' => $amountMinor,
            'currency' => $job->currency,
            'client_signed_at' => now(),
            'va_signed_at' => now(),
            'effective_at' => now(),
        ]);
    }

    public function payPostingFee(MarketplaceJob $job): MarketplaceJob
    {
        $job->update([
            'posting_fee_paid_at' => now(),
            'posting_fee_amount_minor' => 2000,
            'posting_fee_currency' => 'TTD',
        ]);

        if ($job->status === JobStatus::PendingPostingFee) {
            return $this->workflow->postingFeePaid($job);
        }

        return $job->fresh();
    }

    public function workflow(): JobWorkflowService
    {
        return $this->workflow;
    }

    public function ledger(): LedgerService
    {
        return $this->ledger;
    }
}
