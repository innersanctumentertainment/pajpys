<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Services\AuthService;
use App\Services\AuditLogService;
use App\Services\CandidatePrivacyService;
use App\Services\ContactDetectionService;
use App\Services\CurrencyService;
use App\Services\FileStorageService;
use App\Services\JobMatchingService;
use App\Services\JobPostingFeeService;
use App\Services\JobWorkflowService;
use App\Services\ServiceListingService;
use App\Services\LedgerService;
use App\Services\NotificationService;
use App\Services\PlatformSettingsService;
use App\Services\StaffPayoutService;
use App\Services\WiPayGateway;
use App\Services\WithdrawalFeeService;
use App\Services\WithdrawalService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $singletons = [
        PlatformSettingsService::class => PlatformSettingsService::class,
        LedgerService::class => LedgerService::class,
        CurrencyService::class => CurrencyService::class,
        AuditLogService::class => AuditLogService::class,
        NotificationService::class => NotificationService::class,
        ContactDetectionService::class => ContactDetectionService::class,
        FileStorageService::class => FileStorageService::class,
        JobMatchingService::class => JobMatchingService::class,
        JobWorkflowService::class => JobWorkflowService::class,
        JobPostingFeeService::class => JobPostingFeeService::class,
        ServiceListingService::class => ServiceListingService::class,
        WiPayGateway::class => WiPayGateway::class,
        AuthService::class => AuthService::class,
        WithdrawalFeeService::class => WithdrawalFeeService::class,
        WithdrawalService::class => WithdrawalService::class,
        CandidatePrivacyService::class => CandidatePrivacyService::class,
        StaffPayoutService::class => StaffPayoutService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, WiPayGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') && ($root = config('app.url'))) {
            URL::forceRootUrl($root);
            URL::forceScheme('https');
        }
    }
}
