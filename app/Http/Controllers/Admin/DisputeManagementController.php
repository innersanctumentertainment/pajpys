<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DisputeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveDisputeRequest;
use App\Models\Dispute;
use App\Services\AuditLogService;
use App\Services\JobWorkflowService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisputeManagementController extends Controller
{
    public function __construct(
        private readonly JobWorkflowService $workflow,
        private readonly AuditLogService $auditLog,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $disputes = Dispute::query()
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->with(['job', 'raisedBy', 'againstUser'])
            ->latest()
            ->paginate(30);

        return response()->json($disputes);
    }

    public function show(Dispute $dispute): JsonResponse
    {
        return response()->json([
            'dispute' => $dispute->load(['job', 'raisedBy', 'againstUser', 'evidence']),
        ]);
    }

    public function assign(Request $request, Dispute $dispute): JsonResponse
    {
        $dispute->update([
            'assigned_to' => $request->user()->id,
            'status' => DisputeStatus::UnderReview,
        ]);

        $this->auditLog->log('dispute.assigned', $dispute, user: $request->user());

        return response()->json(['dispute' => $dispute->fresh()]);
    }

    public function resolve(ResolveDisputeRequest $request, Dispute $dispute): JsonResponse
    {
        $admin = $request->user();
        $status = DisputeStatus::from($request->validated('status'));

        $dispute->update([
            'status' => $status,
            'resolution_notes' => $request->validated('resolution_notes'),
            'resolved_at' => now(),
            'assigned_to' => $admin->id,
        ]);

        $job = $dispute->job;

        match ($status) {
            DisputeStatus::ResolvedClient, DisputeStatus::Closed => $this->workflow->cancel($job, $admin, 'dispute_resolved'),
            DisputeStatus::ResolvedVa => $this->workflow->complete($job, $admin),
            DisputeStatus::ResolvedSplit => null,
            default => null,
        };

        $dispute->load(['raisedBy', 'againstUser']);

        foreach ([$dispute->raisedBy, $dispute->againstUser] as $participant) {
            if ($participant === null) {
                continue;
            }

            $this->notifications->notify(
                $participant,
                'dispute.resolved',
                'Dispute resolved',
                "The dispute on job \"{$job->title}\" has been resolved.",
                ['dispute_id' => $dispute->id, 'status' => $status->value],
            );
        }

        $this->auditLog->log(
            'dispute.resolved',
            $dispute,
            newValues: ['status' => $status->value],
            user: $admin,
        );

        return response()->json(['dispute' => $dispute->fresh()]);
    }
}
