<?php

namespace App\Console\Commands;

use App\Enums\JobStatus;
use App\Models\MarketplaceJob;
use App\Services\AuditLogService;
use App\Services\JobWorkflowService;
use App\Services\NotificationService;
use App\Services\PlatformSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessAutoReleases extends Command
{
    protected $signature = 'marketplace:auto-releases';

    protected $description = 'Auto-release job funds after configured approval window';

    public function handle(
        PlatformSettingsService $settings,
        JobWorkflowService $workflow,
        NotificationService $notifications,
        AuditLogService $auditLog,
    ): int {
        if (! $settings->get('auto_release_enabled', true)) {
            $this->info('Auto-release disabled.');

            return self::SUCCESS;
        }

        $days = (int) $settings->get('approval_window_days', 7);

        $jobs = MarketplaceJob::query()
            ->where('status', JobStatus::PendingApproval)
            ->where('submitted_at', '<=', now()->subDays($days))
            ->get();

        foreach ($jobs as $job) {
            DB::transaction(function () use ($job, $workflow, $notifications, $auditLog) {
                $job->refresh();

                if ($job->status !== JobStatus::PendingApproval) {
                    return;
                }

                $workflow->complete($job);
                $auditLog->log('job.auto_released', $job, user: null);
                $notifications->notify(
                    $job->client,
                    'job_auto_released',
                    'Work auto-approved',
                    $job->title.' was automatically approved after the review period.',
                    ['job_id' => $job->id],
                );

                foreach ($job->assignments as $assignment) {
                    $notifications->notify(
                        $assignment->va,
                        'funds_released',
                        'Funds released',
                        'Funds for '.$job->title.' are now in your wallet.',
                        ['job_id' => $job->id],
                    );
                }
            });
        }

        $this->info("Auto-released {$jobs->count()} jobs.");

        return self::SUCCESS;
    }
}
