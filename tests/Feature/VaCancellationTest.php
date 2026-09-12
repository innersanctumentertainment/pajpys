<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\CancellationRecord;
use App\Services\PlatformSettingsService;
use Tests\MarketplaceTestCase;
use Tests\Support\MarketplaceScenarioBuilder;

class VaCancellationTest extends MarketplaceTestCase
{
    public function test_scenario_three_va_cancellation_applies_fee(): void
    {
        $builder = app(MarketplaceScenarioBuilder::class);
        $settings = app(PlatformSettingsService::class);
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
        $builder->workflow()->startWork($job, $va);

        $feeMinor = (int) round(((float) $settings->get('va_cancellation_fee', 20)) * 100);
        $cancellation = CancellationRecord::query()->create([
            'marketplace_job_id' => $job->id,
            'cancelled_by' => $va->id,
            'cancelled_by_role' => 'va',
            'reason_code' => 'va_unavailable',
            'reason_detail' => 'Schedule conflict',
            'fee_amount' => $feeMinor / 100,
            'fee_amount_minor' => $feeMinor,
            'currency' => 'TTD',
            'cancelled_at' => now(),
        ]);

        $cancelled = $builder->workflow()->cancel($job, $va, 'Schedule conflict');

        $this->assertSame(JobStatus::Cancelled, $cancelled->status);
        $this->assertSame($feeMinor, $cancellation->fee_amount_minor);
        $this->assertSame('va', $cancellation->cancelled_by_role);
    }
}
