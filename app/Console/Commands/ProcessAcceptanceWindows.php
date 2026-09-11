<?php

namespace App\Console\Commands;

use App\Enums\JobStatus;
use App\Models\MarketplaceJob;
use App\Services\AuditLogService;
use App\Services\JobWorkflowService;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessAcceptanceWindows extends Command
{
    protected $signature = 'marketplace:acceptance-windows';

    protected $description = 'Expire job acceptance windows and notify clients when action is required';

    public function handle(
        JobWorkflowService $workflow,
        NotificationService $notifications,
        AuditLogService $auditLog,
    ): int {
        $expired = MarketplaceJob::query()
            ->where('status', JobStatus::Accepting)
            ->whereNotNull('acceptance_ends_at')
            ->where('acceptance_ends_at', '<=', now())
            ->get();

        foreach ($expired as $job) {
            DB::transaction(function () use ($job, $workflow, $notifications, $auditLog) {
                $job->refresh();

                if ($job->status !== JobStatus::Accepting) {
                    return;
                }

                $workflow->startSelection($job);
                $auditLog->log('job.acceptance_window_expired', $job, user: null);
                $notifications->notify(
                    $job->client,
                    'acceptance_window_expired',
                    'Action required',
                    'Review candidates for '.$job->title,
                    ['job_id' => $job->id],
                );
            });
        }

        $this->info("Processed {$expired->count()} acceptance windows.");

        return self::SUCCESS;
    }
}
