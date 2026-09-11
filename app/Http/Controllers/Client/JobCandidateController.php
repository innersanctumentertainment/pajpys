<?php

namespace App\Http\Controllers\Client;

use App\Enums\JobStatus;
use App\Http\Concerns\AuthorizesMarketplaceJob;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\InviteVaRequest;
use App\Http\Requests\Client\SelectCandidateRequest;
use App\Models\JobCandidate;
use App\Models\JobInvitation;
use App\Models\MarketplaceJob;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\JobWorkflowService;
use App\Services\NotificationService;
use App\Services\PlatformSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobCandidateController extends Controller
{
    use AuthorizesMarketplaceJob;

    public function __construct(
        private readonly JobWorkflowService $workflow,
        private readonly NotificationService $notifications,
        private readonly AuditLogService $auditLog,
        private readonly PlatformSettingsService $settings,
    ) {}

    public function index(Request $request, MarketplaceJob $job): JsonResponse
    {
        $this->authorizeClientJob($request->user(), $job);

        $candidates = $job->candidates()
            ->with(['va.vaProfile', 'va.skills.skill'])
            ->latest()
            ->get();

        $invitations = $job->invitations()
            ->with('va.vaProfile')
            ->latest()
            ->get();

        return response()->json([
            'candidates' => $candidates,
            'invitations' => $invitations,
        ]);
    }

    public function select(SelectCandidateRequest $request, MarketplaceJob $job): JsonResponse
    {
        $user = $request->user();
        $this->authorizeClientJob($user, $job);

        if (! in_array($job->status, [JobStatus::Accepting, JobStatus::Selecting], true)) {
            return response()->json(['message' => 'Job is not accepting candidate selection.'], 422);
        }

        $candidate = JobCandidate::query()
            ->where('marketplace_job_id', $job->id)
            ->findOrFail($request->validated('candidate_id'));

        $candidate->update(['status' => 'selected']);
        $job->candidates()
            ->where('id', '!=', $candidate->id)
            ->where('status', 'applied')
            ->update(['status' => 'rejected']);

        if ($job->status === JobStatus::Accepting) {
            $this->workflow->startSelection($job, $user);
        }

        $va = User::query()->findOrFail($candidate->va_id);
        $job = $this->workflow->assign($job, $va, $user);

        $this->notifications->notify(
            $va,
            'job.assigned',
            'You have been selected',
            "You were selected for job \"{$job->title}\".",
            ['marketplace_job_id' => $job->id],
        );

        $this->auditLog->log(
            'job.candidate_selected',
            $job,
            newValues: ['candidate_id' => $candidate->id, 'va_id' => $va->id],
            user: $user,
        );

        return response()->json(['job' => $job->fresh(), 'candidate' => $candidate->fresh()]);
    }

    public function invite(InviteVaRequest $request, MarketplaceJob $job): JsonResponse
    {
        $user = $request->user();
        $this->authorizeClientJob($user, $job);

        $va = User::query()->findOrFail($request->validated('va_id'));

        if (! $va->isVaApproved()) {
            return response()->json(['message' => 'Selected user is not an approved VA.'], 422);
        }

        $expiryHours = $this->settings->getInt('job_invitation_expiry_hours', 72);

        $invitation = JobInvitation::query()->updateOrCreate(
            [
                'marketplace_job_id' => $job->id,
                'va_id' => $va->id,
            ],
            [
                'client_id' => $user->id,
                'status' => 'pending',
                'message' => $request->validated('message'),
                'expires_at' => now()->addHours($expiryHours),
            ],
        );

        $this->notifications->notify(
            $va,
            'job.invitation',
            'Job invitation',
            "You were invited to apply for \"{$job->title}\".",
            ['marketplace_job_id' => $job->id, 'invitation_id' => $invitation->id],
        );

        $this->auditLog->log(
            'job.va_invited',
            $job,
            newValues: ['va_id' => $va->id, 'invitation_id' => $invitation->id],
            user: $user,
        );

        return response()->json(['invitation' => $invitation], 201);
    }
}
