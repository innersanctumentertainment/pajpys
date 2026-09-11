<?php

namespace Tests\Unit;

use App\Enums\JobStatus;
use App\Models\MarketplaceJob;
use App\Services\JobWorkflowService;
use InvalidArgumentException;
use Tests\MarketplaceTestCase;
use Tests\Support\MarketplaceScenarioBuilder;

class JobWorkflowServiceTest extends MarketplaceTestCase
{
    private JobWorkflowService $workflow;

    private MarketplaceScenarioBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflow = app(JobWorkflowService::class);
        $this->builder = app(MarketplaceScenarioBuilder::class);
    }

    public function test_valid_transition_from_draft_to_pending_posting_fee(): void
    {
        $client = $this->builder->createClient();
        $job = $this->builder->createJob($client);
        $updated = $this->workflow->publish($job, $client);
        $this->assertSame(JobStatus::PendingPostingFee, $updated->status);
    }

    public function test_valid_happy_path_transitions(): void
    {
        $client = $this->builder->createClient();
        $va = $this->builder->createVa();
        $job = $this->builder->createJob($client);
        $this->workflow->publish($job, $client);
        $this->builder->payPostingFee($job);
        $this->workflow->markPaid($job, $client);
        $this->workflow->openForAcceptance($job, $client);
        $this->workflow->startSelection($job, $client);
        $this->workflow->assign($job, $va, $client);
        $this->workflow->startWork($job, $va);
        $this->workflow->submitForApproval($job, $va);
        $completed = $this->workflow->complete($job, $client);
        $this->assertSame(JobStatus::Completed, $completed->status);
        $this->assertNotNull($completed->closed_at);
    }

    public function test_invalid_transition_throws_exception(): void
    {
        $client = $this->builder->createClient();
        $job = MarketplaceJob::query()->create([
            'client_id' => $client->id,
            'title' => 'Draft Job',
            'slug' => 'draft-job-invalid',
            'description' => 'Test',
            'status' => JobStatus::Draft,
            'currency' => 'TTD',
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->workflow->transition($job, JobStatus::Completed, $client);
    }

    public function test_cancelled_is_terminal(): void
    {
        $client = $this->builder->createClient();
        $job = $this->builder->createJob($client);
        $this->workflow->publish($job, $client);
        $this->builder->payPostingFee($job);
        $cancelled = $this->workflow->cancel($job, $client, 'client changed mind');
        $this->assertSame(JobStatus::Cancelled, $cancelled->status);
        $this->assertSame([], $this->workflow->availableTransitions($cancelled));
    }
}
