<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Dispute;
use Tests\MarketplaceTestCase;
use Tests\Support\MarketplaceScenarioBuilder;

class DisputeTest extends MarketplaceTestCase
{
    public function test_scenario_five_dispute_flow(): void
    {
        $builder = app(MarketplaceScenarioBuilder::class);
        $client = $builder->createClient();
        $va = $builder->createVa();
        $job = $builder->createJob($client, 10000);

        $builder->workflow()->publish($job, $client);
        $builder->payPostingFee($job);
        $builder->workflow()->markPaid($job, $client);
        $builder->workflow()->openForAcceptance($job, $client);
        $builder->applyAsCandidate($job, $va);
        $builder->workflow()->startSelection($job, $client);
        $builder->workflow()->assign($job, $va, $client);
        $builder->createAgreement($job, $va, 10000);
        $builder->workflow()->startWork($job, $va);
        $builder->workflow()->submitForApproval($job, $va);

        $disputed = $builder->workflow()->dispute($job, $client, 'Deliverables incomplete');
        $dispute = Dispute::query()->create([
            'marketplace_job_id' => $job->id,
            'raised_by' => $client->id,
            'against_user_id' => $va->id,
            'status' => 'open',
            'reason_code' => 'quality_issue',
            'description' => 'Deliverables incomplete',
            'disputed_amount' => 100,
            'disputed_amount_minor' => 10000,
            'currency' => 'TTD',
        ]);

        $this->assertSame(JobStatus::Disputed, $disputed->status);
        $this->assertSame('open', $dispute->status);

        $resolved = $builder->workflow()->transition($job->fresh(), JobStatus::InProgress, $client);
        $dispute->update(['status' => 'resolved', 'resolved_at' => now()]);

        $this->assertSame(JobStatus::InProgress, $resolved->status);
        $this->assertSame('resolved', $dispute->fresh()->status);
    }
}
