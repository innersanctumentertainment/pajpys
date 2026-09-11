<?php

namespace App\Http\Controllers\Va;

use App\Enums\JobStatus;
use App\Http\Concerns\AuthorizesMarketplaceJob;
use App\Http\Controllers\Controller;
use App\Http\Requests\Va\CompleteDeliverableRequest;
use App\Http\Requests\Va\SubmitCompletionEvidenceRequest;
use App\Models\JobAssignment;
use App\Models\JobCompletionEvidence;
use App\Models\JobDeliverable;
use App\Models\MarketplaceJob;
use App\Services\AuditLogService;
use App\Services\FileStorageService;
use App\Services\JobWorkflowService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobWorkController extends Controller
{
    use AuthorizesMarketplaceJob;

    public function __construct(
        private readonly JobWorkflowService $workflow,
        private readonly FileStorageService $fileStorage,
        private readonly NotificationService $notifications,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $assignments = JobAssignment::query()
            ->where('va_id', $request->user()->id)
            ->whereIn('status', ['active', 'completed'])
            ->with(['job.category', 'job.deliverables'])
            ->latest()
            ->paginate(20);

        return response()->json($assignments);
    }

    public function show(Request $request, MarketplaceJob $job): JsonResponse
    {
        $assignment = $this->resolveAssignment($request, $job);

        return response()->json([
            'job' => $job->load(['deliverables.evidence', 'client']),
            'assignment' => $assignment,
        ]);
    }

    public function startWork(Request $request, MarketplaceJob $job): JsonResponse
    {
        $this->resolveAssignment($request, $job);

        if ($job->status !== JobStatus::Assigned) {
            return response()->json(['message' => 'Job must be assigned before starting work.'], 422);
        }

        $job = $this->workflow->startWork($job, $request->user());

        $this->notifications->notify(
            $job->client,
            'job.work_started',
            'Work started',
            "The VA started work on \"{$job->title}\".",
            ['marketplace_job_id' => $job->id],
        );

        return response()->json(['job' => $job]);
    }

    public function completeDeliverable(
        CompleteDeliverableRequest $request,
        MarketplaceJob $job,
    ): JsonResponse {
        $user = $request->user();
        $assignment = $this->resolveAssignment($request, $job);

        $deliverable = JobDeliverable::query()->create([
            'marketplace_job_id' => $job->id,
            'job_assignment_id' => $assignment->id,
            'submitted_by' => $user->id,
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->auditLog->log(
            'job.deliverable_submitted',
            $deliverable,
            user: $user,
        );

        return response()->json(['deliverable' => $deliverable], 201);
    }

    public function submitEvidence(
        SubmitCompletionEvidenceRequest $request,
        MarketplaceJob $job,
    ): JsonResponse {
        $user = $request->user();
        $this->resolveAssignment($request, $job);

        $stored = $this->fileStorage->storePrivate(
            $request->file('file'),
            $user,
            [$job->client_id],
        );

        $evidence = JobCompletionEvidence::query()->create([
            'marketplace_job_id' => $job->id,
            'job_deliverable_id' => $request->validated('deliverable_id'),
            'uploaded_by' => $user->id,
            'file_path' => $stored->path,
            'original_filename' => $stored->original_name,
            'mime_type' => $stored->mime_type,
            'file_size' => $stored->size,
            'notes' => $request->validated('notes'),
        ]);

        return response()->json(['evidence' => $evidence], 201);
    }

    public function submitForApproval(Request $request, MarketplaceJob $job): JsonResponse
    {
        $this->resolveAssignment($request, $job);

        if ($job->status !== JobStatus::InProgress) {
            return response()->json(['message' => 'Job must be in progress to submit for approval.'], 422);
        }

        $job = $this->workflow->submitForApproval($job, $request->user());

        $this->notifications->notify(
            $job->client,
            'job.pending_approval',
            'Work submitted for approval',
            "The VA submitted work on \"{$job->title}\" for your review.",
            ['marketplace_job_id' => $job->id],
        );

        return response()->json(['job' => $job]);
    }

    private function resolveAssignment(Request $request, MarketplaceJob $job): JobAssignment
    {
        $assignment = $job->assignments()
            ->where('va_id', $request->user()->id)
            ->whereIn('status', ['active', 'completed'])
            ->first();

        if ($assignment === null) {
            abort(403, 'You are not assigned to this job.');
        }

        return $assignment;
    }
}
