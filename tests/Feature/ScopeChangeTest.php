<?php

namespace Tests\Feature;

use App\Models\JobAgreement;
use Tests\MarketplaceTestCase;
use Tests\Support\MarketplaceScenarioBuilder;

class ScopeChangeTest extends MarketplaceTestCase
{
    public function test_scenario_four_scope_change_updates_agreement(): void
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

        $original = $builder->createAgreement($job, $va, 10000);
        $original->update(['status' => 'superseded']);

        $revised = JobAgreement::query()->create([
            'marketplace_job_id' => $job->id,
            'client_id' => $client->id,
            'va_id' => $va->id,
            'status' => 'active',
            'agreed_amount' => 175,
            'agreed_amount_minor' => 17500,
            'currency' => 'TTD',
            'terms' => 'Added social media management to scope',
            'client_signed_at' => now(),
            'va_signed_at' => now(),
            'effective_at' => now(),
        ]);

        $builder->workflow()->startWork($job, $va);

        $this->assertSame('superseded', $original->fresh()->status);
        $this->assertSame(17500, $revised->agreed_amount_minor);
        $this->assertStringContainsString('social media', $revised->terms);
    }
}
