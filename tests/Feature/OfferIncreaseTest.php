<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Offer;
use Tests\MarketplaceTestCase;
use Tests\Support\MarketplaceScenarioBuilder;

class OfferIncreaseTest extends MarketplaceTestCase
{
    public function test_scenario_two_client_increases_offer_amount(): void
    {
        $builder = app(MarketplaceScenarioBuilder::class);
        $client = $builder->createClient();
        $va = $builder->createVa();
        $job = $builder->createJob($client, 10000);

        $builder->workflow()->publish($job, $client);
        $builder->payPostingFee($job);
        $builder->workflow()->markPaid($job, $client);
        $builder->workflow()->openForAcceptance($job, $client);
        $candidate = $builder->applyAsCandidate($job, $va, 10000);

        $initialOffer = Offer::query()->create([
            'marketplace_job_id' => $job->id,
            'client_id' => $client->id,
            'va_id' => $va->id,
            'job_candidate_id' => $candidate->id,
            'status' => 'pending',
            'amount' => 100,
            'amount_minor' => 10000,
            'currency' => 'TTD',
        ]);

        $increasedOffer = Offer::query()->create([
            'marketplace_job_id' => $job->id,
            'client_id' => $client->id,
            'va_id' => $va->id,
            'job_candidate_id' => $candidate->id,
            'status' => 'pending',
            'amount' => 150,
            'amount_minor' => 15000,
            'currency' => 'TTD',
            'message' => 'Increased offer for expanded scope',
        ]);

        $initialOffer->update(['status' => 'superseded']);
        $builder->workflow()->startSelection($job, $client);
        $builder->workflow()->assign($job, $va, $client);
        $agreement = $builder->createAgreement($job, $va, 15000);

        $this->assertSame(15000, $agreement->agreed_amount_minor);
        $this->assertSame('superseded', $initialOffer->fresh()->status);
        $this->assertSame('pending', $increasedOffer->fresh()->status);
        $this->assertSame(JobStatus::Assigned, $job->fresh()->status);
    }
}
