<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\LedgerAccountType;
use App\Services\WithdrawalService;
use Tests\MarketplaceTestCase;
use Tests\Support\MarketplaceScenarioBuilder;

class JobLifecycleTest extends MarketplaceTestCase
{
    public function test_scenario_one_full_job_lifecycle_with_va_withdrawal(): void
    {
        $builder = app(MarketplaceScenarioBuilder::class);
        $withdrawals = app(WithdrawalService::class);

        $client = $builder->createClient();
        $va = $builder->createVa();
        $job = $builder->createJob($client, 10000);

        $builder->fundClientWallet($client, 10000);
        $builder->workflow()->publish($job, $client);
        $builder->payPostingFee($job);
        $builder->workflow()->markPaid($job, $client);
        $builder->workflow()->openForAcceptance($job, $client);

        $candidate = $builder->applyAsCandidate($job, $va, 10000);
        $this->assertSame('applied', $candidate->status);

        $builder->workflow()->startSelection($job, $client);
        $builder->workflow()->assign($job, $va, $client);
        $this->assertSame(JobStatus::Assigned, $job->fresh()->status);

        $agreement = $builder->createAgreement($job, $va, 10000);
        $this->assertNotNull($agreement->effective_at);

        $builder->workflow()->startWork($job, $va);
        $builder->workflow()->submitForApproval($job, $va);
        $builder->workflow()->complete($job, $client);

        $this->assertSame(JobStatus::Completed, $job->fresh()->status);

        $builder->fundVaWallet($va, 20000);
        $withdrawal = $withdrawals->requestVaWithdrawal($va, 20000);

        $this->assertSame('pending', $withdrawal->status);
        $this->assertSame(3000, $withdrawal->fee_amount_minor);
        $this->assertSame(17000, $withdrawal->net_amount_minor);

        $vaAccount = $builder->ledger()->createAccount($va, LedgerAccountType::VaWallet, 'TTD');
        $this->assertSame(0, $builder->ledger()->getBalance($vaAccount));
    }
}
