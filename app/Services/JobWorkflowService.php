<?php

namespace App\Services;

use App\Enums\JobStatus;
use App\Models\JobAssignment;
use App\Models\MarketplaceJob;
use App\Models\User;
use InvalidArgumentException;

class JobWorkflowService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function transition(
        MarketplaceJob $job,
        JobStatus $to,
        ?User $actor = null,
        array $context = [],
    ): MarketplaceJob {
        $from = $job->status;

        if ($from === $to) {
            return $job;
        }

        if (! $from->canTransitionTo($to)) {
            throw new InvalidArgumentException(
                "Invalid job transition from [{$from->value}] to [{$to->value}].",
            );
        }

        $updates = ['status' => $to];

        if ($to === JobStatus::Open && $job->published_at === null) {
            $updates['published_at'] = now();
        }

        if ($to === JobStatus::Completed) {
            $updates['closed_at'] = now();
        }

        if ($to === JobStatus::Cancelled) {
            $updates['closed_at'] = now();
        }

        $job->update($updates);

        $this->auditLog->log(
            event: 'job.status_changed',
            auditable: $job,
            oldValues: ['status' => $from->value],
            newValues: array_merge(['status' => $to->value], $context),
            user: $actor,
        );

        return $job->fresh();
    }

    public function publish(MarketplaceJob $job, ?User $actor = null): MarketplaceJob
    {
        if ($job->status === JobStatus::Draft) {
            return $this->transition($job, JobStatus::PendingPostingFee, $actor);
        }

        return $this->transition($job, JobStatus::Open, $actor);
    }

    public function postingFeePaid(MarketplaceJob $job, ?User $actor = null): MarketplaceJob
    {
        return $this->transition($job, JobStatus::PendingPayment, $actor, ['reason' => 'posting_fee_paid']);
    }

    public function markPaid(MarketplaceJob $job, ?User $actor = null): MarketplaceJob
    {
        return $this->transition($job, JobStatus::Open, $actor, ['reason' => 'payment_confirmed']);
    }

    public function openForAcceptance(MarketplaceJob $job, ?User $actor = null): MarketplaceJob
    {
        return $this->transition($job, JobStatus::Accepting, $actor);
    }

    public function startSelection(MarketplaceJob $job, ?User $actor = null): MarketplaceJob
    {
        return $this->transition($job, JobStatus::Selecting, $actor);
    }

    public function assign(MarketplaceJob $job, User $va, ?User $actor = null): MarketplaceJob
    {
        JobAssignment::query()->updateOrCreate(
            [
                'marketplace_job_id' => $job->id,
                'va_id' => $va->id,
            ],
            [
                'client_id' => $job->client_id,
                'status' => 'active',
                'assigned_at' => now(),
            ],
        );

        return $this->transition($job, JobStatus::Assigned, $actor, ['assigned_va_id' => $va->id]);
    }

    public function startWork(MarketplaceJob $job, ?User $actor = null): MarketplaceJob
    {
        $job->assignments()
            ->where('status', 'active')
            ->update(['started_at' => now()]);

        return $this->transition($job, JobStatus::InProgress, $actor);
    }

    public function submitForApproval(MarketplaceJob $job, ?User $actor = null): MarketplaceJob
    {
        return $this->transition($job, JobStatus::PendingApproval, $actor);
    }

    public function complete(MarketplaceJob $job, ?User $actor = null): MarketplaceJob
    {
        $job->assignments()
            ->where('status', 'active')
            ->update(['completed_at' => now(), 'status' => 'completed']);

        return $this->transition($job, JobStatus::Completed, $actor);
    }

    public function cancel(MarketplaceJob $job, ?User $actor = null, ?string $reason = null): MarketplaceJob
    {
        $job->assignments()
            ->where('status', 'active')
            ->update(['cancelled_at' => now(), 'status' => 'cancelled']);

        return $this->transition($job, JobStatus::Cancelled, $actor, ['reason' => $reason]);
    }

    public function dispute(MarketplaceJob $job, ?User $actor = null, ?string $reason = null): MarketplaceJob
    {
        return $this->transition($job, JobStatus::Disputed, $actor, ['reason' => $reason]);
    }

    /**
     * @return list<JobStatus>
     */
    public function availableTransitions(MarketplaceJob $job): array
    {
        return $job->status->allowedTransitions();
    }
}
