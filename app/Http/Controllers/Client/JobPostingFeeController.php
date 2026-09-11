<?php

namespace App\Http\Controllers\Client;

use App\Enums\JobStatus;
use App\Http\Concerns\AuthorizesMarketplaceJob;
use App\Http\Controllers\Controller;
use App\Models\MarketplaceJob;
use App\Services\JobPostingFeeService;
use App\Services\JobWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobPostingFeeController extends Controller
{
    use AuthorizesMarketplaceJob;

    public function __construct(
        private readonly JobPostingFeeService $postingFee,
        private readonly JobWorkflowService $workflow,
    ) {}

    public function show(Request $request, MarketplaceJob $job): View|JsonResponse
    {
        $this->authorizeClientJob($request->user(), $job);

        if ($job->status !== JobStatus::PendingPostingFee && $job->posting_fee_paid_at !== null) {
            return redirect()->route('client.jobs.show', $job);
        }

        $data = [
            'job' => $job,
            'feeAmount' => $this->postingFee->feeAmountMinor() / 100,
            'feeCurrency' => $this->postingFee->feeCurrency(),
            'active' => 'client.jobs',
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('client.jobs.posting-fee', $data);
    }

    public function initiate(Request $request, MarketplaceJob $job): JsonResponse
    {
        $this->authorizeClientJob($request->user(), $job);

        if ($job->status !== JobStatus::PendingPostingFee) {
            return response()->json(['message' => 'Job is not awaiting posting fee.'], 422);
        }

        $request->validate(['idempotency_key' => ['required', 'string', 'max:128']]);

        $result = $this->postingFee->initiatePayment(
            $job,
            $request->user(),
            $request->string('idempotency_key'),
        );

        return response()->json($result);
    }

    public function confirm(Request $request, MarketplaceJob $job): JsonResponse
    {
        $this->authorizeClientJob($request->user(), $job);

        if ($job->posting_fee_paid_at === null) {
            return response()->json(['message' => 'Posting fee not yet confirmed.'], 422);
        }

        if ($job->status === JobStatus::PendingPostingFee) {
            $this->workflow->postingFeePaid($job, $request->user());
        }

        return response()->json(['job' => $job->fresh()]);
    }
}
