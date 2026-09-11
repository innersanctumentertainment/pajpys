<?php

namespace App\Http\Controllers;

use App\Enums\DisputeStatus;
use App\Http\Concerns\AuthorizesMarketplaceJob;
use App\Http\Requests\OpenDisputeRequest;
use App\Http\Requests\SubmitDisputeEvidenceRequest;
use App\Models\Dispute;
use App\Models\DisputeEvidence;
use App\Models\MarketplaceJob;
use App\Services\AuditLogService;
use App\Services\FileStorageService;
use App\Services\JobWorkflowService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisputeController extends Controller
{
    use AuthorizesMarketplaceJob;

    public function __construct(
        private readonly JobWorkflowService $workflow,
        private readonly FileStorageService $fileStorage,
        private readonly NotificationService $notifications,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request, MarketplaceJob $job): JsonResponse
    {
        $this->authorizeJobParticipant($request->user(), $job);

        $disputes = $job->disputes()
            ->with(['raisedBy', 'againstUser', 'evidence'])
            ->latest()
            ->get();

        return response()->json(['disputes' => $disputes]);
    }

    public function store(OpenDisputeRequest $request, MarketplaceJob $job): JsonResponse
    {
        $user = $request->user();
        $this->authorizeJobParticipant($user, $job);

        $data = $request->validated();
        $disputedAmount = $data['disputed_amount'] ?? $job->budget_amount;

        $dispute = Dispute::query()->create([
            'marketplace_job_id' => $job->id,
            'raised_by' => $user->id,
            'against_user_id' => $data['against_user_id'],
            'status' => DisputeStatus::Open,
            'reason_code' => $data['reason_code'],
            'description' => $data['description'],
            'disputed_amount' => $disputedAmount,
            'disputed_amount_minor' => $disputedAmount !== null
                ? (int) round((float) $disputedAmount * 100)
                : $job->budget_amount_minor,
            'currency' => $job->currency,
        ]);

        $this->workflow->dispute($job, $user, $data['reason_code']);

        $this->notifications->notify(
            \App\Models\User::query()->findOrFail($data['against_user_id']),
            'dispute.opened',
            'Dispute opened',
            "A dispute was opened on job \"{$job->title}\".",
            ['marketplace_job_id' => $job->id, 'dispute_id' => $dispute->id],
        );

        $this->auditLog->log('dispute.opened', $dispute, user: $user);

        return response()->json(['dispute' => $dispute], 201);
    }

    public function show(Request $request, Dispute $dispute): JsonResponse
    {
        $job = $dispute->job;
        $this->authorizeJobParticipant($request->user(), $job);

        return response()->json(['dispute' => $dispute->load(['evidence', 'raisedBy', 'againstUser'])]);
    }

    public function submitEvidence(
        SubmitDisputeEvidenceRequest $request,
        Dispute $dispute,
    ): JsonResponse {
        $user = $request->user();
        $job = $dispute->job;
        $this->authorizeJobParticipant($user, $job);

        $stored = $this->fileStorage->storePrivate(
            $request->file('file'),
            $user,
            [$job->client_id, $dispute->against_user_id],
        );

        $evidence = DisputeEvidence::query()->create([
            'dispute_id' => $dispute->id,
            'uploaded_by' => $user->id,
            'file_path' => $stored->path,
            'original_filename' => $stored->original_name,
            'mime_type' => $stored->mime_type,
            'file_size' => $stored->size,
            'description' => $request->validated('description'),
        ]);

        $this->auditLog->log('dispute.evidence_submitted', $evidence, user: $user);

        return response()->json(['evidence' => $evidence], 201);
    }
}
