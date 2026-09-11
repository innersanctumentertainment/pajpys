<?php

namespace App\Http\Controllers\Va;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Va\AcceptJobRequest;
use App\Http\Requests\Va\UpdateNotificationPreferencesRequest;
use App\Models\JobCandidate;
use App\Models\JobInvitation;
use App\Models\MarketplaceJob;
use App\Models\NotificationPreference;
use App\Services\AuditLogService;
use App\Services\JobMatchingService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobDiscoveryController extends Controller
{
    public function __construct(
        private readonly JobMatchingService $matching,
        private readonly NotificationService $notifications,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): JsonResponse|View
    {
        $user = $request->user();

        $jobs = MarketplaceJob::query()
            ->where('status', JobStatus::Accepting)
            ->where('visibility', 'public')
            ->with(['category', 'client', 'jobSkills.skill'])
            ->latest('published_at')
            ->paginate(20);

        $recommended = MarketplaceJob::query()
            ->where('status', JobStatus::Accepting)
            ->where('visibility', 'public')
            ->with(['category', 'jobSkills.skill'])
            ->get()
            ->map(fn (MarketplaceJob $job) => [
                'job' => $job,
                'match' => $this->matching->matchForJob($job, 1)->first(),
            ])
            ->filter(fn (array $row) => $row['match'] !== null)
            ->sortByDesc(fn (array $row) => $row['match']['score'])
            ->take(10)
            ->values();

        $invitations = JobInvitation::query()
            ->where('va_id', $user->id)
            ->where('status', 'pending')
            ->with(['job.category', 'client'])
            ->latest()
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'jobs' => $jobs,
                'recommended' => $recommended,
                'invitations' => $invitations,
            ]);
        }

        return view('va.jobs.discover', compact('jobs', 'recommended', 'invitations'));
    }

    public function show(Request $request, MarketplaceJob $job): JsonResponse|View
    {
        if ($job->status !== JobStatus::Accepting && $job->visibility !== 'public') {
            $hasInvitation = JobInvitation::query()
                ->where('marketplace_job_id', $job->id)
                ->where('va_id', $request->user()->id)
                ->exists();

            if (! $hasInvitation) {
                abort(403);
            }
        }

        $match = $this->matching->matchForJob($job, 1)->first();
        $hasApplied = JobCandidate::query()
            ->where('marketplace_job_id', $job->id)
            ->where('va_id', $request->user()->id)
            ->exists();

        $job->load(['category', 'jobSkills.skill']);

        if ($request->wantsJson()) {
            return response()->json([
                'job' => $job,
                'match' => $match,
                'has_applied' => $hasApplied,
            ]);
        }

        return view('va.jobs.show', compact('job', 'match', 'hasApplied'));
    }

    public function accept(AcceptJobRequest $request, MarketplaceJob $job): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($job->status !== JobStatus::Accepting) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Job is not accepting applications.'], 422);
            }

            return back()->with('error', 'Job is not accepting applications.');
        }

        $data = $request->validated();
        $proposedAmount = $data['proposed_amount'] ?? null;

        $candidate = JobCandidate::query()->updateOrCreate(
            [
                'marketplace_job_id' => $job->id,
                'va_id' => $user->id,
            ],
            [
                'status' => 'applied',
                'cover_letter' => $data['cover_letter'] ?? null,
                'proposed_amount' => $proposedAmount,
                'proposed_amount_minor' => $proposedAmount !== null
                    ? (int) round((float) $proposedAmount * 100)
                    : null,
                'currency' => $job->currency,
            ],
        );

        JobInvitation::query()
            ->where('marketplace_job_id', $job->id)
            ->where('va_id', $user->id)
            ->update(['status' => 'accepted', 'responded_at' => now()]);

        $this->notifications->notify(
            $job->client,
            'job.application_received',
            'New application',
            "A VA applied to your job \"{$job->title}\".",
            ['marketplace_job_id' => $job->id, 'candidate_id' => $candidate->id],
        );

        $this->auditLog->log(
            'job.application_submitted',
            $job,
            newValues: ['candidate_id' => $candidate->id],
            user: $user,
        );

        if ($request->wantsJson()) {
            return response()->json(['candidate' => $candidate], 201);
        }

        return redirect()
            ->route('va.jobs.show', $job)
            ->with('success', 'Your application has been submitted.');
    }

    public function notificationPreferences(Request $request): JsonResponse
    {
        $preferences = NotificationPreference::query()
            ->where('user_id', $request->user()->id)
            ->get();

        return response()->json(['preferences' => $preferences]);
    }

    public function updateNotificationPreferences(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $user = $request->user();
        $updated = [];

        foreach ($request->validated('preferences') as $pref) {
            $updated[] = NotificationPreference::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'channel' => $pref['channel'],
                    'notification_type' => $pref['notification_type'],
                ],
                ['is_enabled' => $pref['is_enabled']],
            );
        }

        return response()->json(['preferences' => $updated]);
    }
}
