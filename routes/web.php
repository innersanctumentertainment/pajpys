<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DisputeManagementController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\StaffRoleController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\VerificationReviewController;
use App\Http\Controllers\Client\JobCandidateController;
use App\Http\Controllers\Client\JobController as ClientJobController;
use App\Http\Controllers\Client\JobFundingController;
use App\Http\Controllers\Client\JobTemplateController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Va\JobDiscoveryController;
use App\Http\Controllers\Va\JobWorkController;
use App\Http\Controllers\Va\ProfileController as VaProfileController;
use App\Http\Controllers\Va\VerificationController as VaVerificationController;
use App\Http\Controllers\Client\JobPostingFeeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Provider\ServiceListingController;
use App\Http\Controllers\RoleSwitchController;
use App\Http\Controllers\ServiceBrowseController;
use App\Models\ServiceListing;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\Webhook\WiPayWebhookController;
use App\Models\MarketplaceJob;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::post('/deploy/migrate', [DeployController::class, 'migrate'])
    ->middleware('throttle:6,1')
    ->name('deploy.migrate');

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/services', [ServiceBrowseController::class, 'index'])->name('services.browse');
Route::get('/services/{service:slug}', [ServiceBrowseController::class, 'show'])->name('services.show');
Route::post('/services/{service:slug}/inquire', [ServiceBrowseController::class, 'inquire'])
    ->middleware(['auth', 'verified.email'])
    ->name('services.inquire');

Route::get('/dashboard', function () {
    return view('dashboard', ['user' => auth()->user()]);
})->middleware(['auth', 'active.role'])->name('dashboard');

Route::post('/role/switch', [RoleSwitchController::class, 'store'])
    ->middleware(['auth', 'active.role'])
    ->name('role.switch');

Route::bind('job', fn (string $value) => MarketplaceJob::query()->findOrFail($value));
Route::bind('service', fn (string $value) => ServiceListing::query()->where('slug', $value)->firstOrFail());

Route::post('/webhooks/wipay', [WiPayWebhookController::class, 'handle'])
    ->name('webhooks.wipay');

Route::middleware(['auth', 'verified.email'])->group(function () {
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/unread', [NotificationController::class, 'unread'])->name('unread');
        Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::post('/{notificationId}/read', [NotificationController::class, 'markRead'])->name('read');
    });

    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::get('/withdrawals', [WalletController::class, 'withdrawals'])->name('withdrawals');
        Route::post('/withdrawals', [WalletController::class, 'requestWithdrawal'])->name('withdrawals.request');
    });

    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::prefix('jobs/{job}')->name('jobs.')->group(function () {
        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
        Route::post('/messages/{message}/read', [MessageController::class, 'markRead'])->name('messages.read');

        Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');

        Route::get('/disputes', [DisputeController::class, 'index'])->name('disputes.index');
        Route::post('/disputes', [DisputeController::class, 'store'])->name('disputes.store');
    });

    Route::get('/disputes/{dispute}', [DisputeController::class, 'show'])->name('disputes.show');
    Route::post('/disputes/{dispute}/evidence', [DisputeController::class, 'submitEvidence'])->name('disputes.evidence');

    Route::prefix('client')->name('client.')->middleware('role:client')->group(function () {
        Route::get('/jobs', [ClientJobController::class, 'index'])->name('jobs.index');
        Route::post('/jobs', [ClientJobController::class, 'store'])->name('jobs.store');
        Route::get('/jobs/{job}', [ClientJobController::class, 'show'])->name('jobs.show');
        Route::delete('/jobs/{job}', [ClientJobController::class, 'destroy'])->name('jobs.destroy');

        Route::put('/jobs/{job}/wizard/basic', [ClientJobController::class, 'updateBasic'])->name('jobs.wizard.basic');
        Route::put('/jobs/{job}/wizard/price', [ClientJobController::class, 'updatePrice'])->name('jobs.wizard.price');
        Route::put('/jobs/{job}/wizard/deadline', [ClientJobController::class, 'updateDeadline'])->name('jobs.wizard.deadline');
        Route::put('/jobs/{job}/wizard/visibility', [ClientJobController::class, 'updateVisibility'])->name('jobs.wizard.visibility');
        Route::get('/jobs/{job}/wizard/review', [ClientJobController::class, 'review'])->name('jobs.wizard.review');
        Route::post('/jobs/{job}/publish', [ClientJobController::class, 'publish'])->name('jobs.publish');
        Route::get('/jobs/{job}/posting-fee', [JobPostingFeeController::class, 'show'])->name('jobs.posting-fee');
        Route::post('/jobs/{job}/posting-fee', [JobPostingFeeController::class, 'initiate'])->name('jobs.posting-fee.initiate');
        Route::post('/jobs/{job}/posting-fee/confirm', [JobPostingFeeController::class, 'confirm'])->name('jobs.posting-fee.confirm');
        Route::post('/jobs/{job}/go-live', [ClientJobController::class, 'goLive'])->name('jobs.go-live');

        Route::post('/jobs/{job}/funding', [JobFundingController::class, 'initiate'])->name('jobs.funding.initiate');
        Route::post('/jobs/{job}/funding/confirm', [JobFundingController::class, 'confirm'])->name('jobs.funding.confirm');

        Route::get('/jobs/{job}/candidates', [JobCandidateController::class, 'index'])->name('jobs.candidates.index');
        Route::post('/jobs/{job}/candidates/select', [JobCandidateController::class, 'select'])->name('jobs.candidates.select');
        Route::post('/jobs/{job}/invitations', [JobCandidateController::class, 'invite'])->name('jobs.invitations.store');

        Route::get('/templates', [JobTemplateController::class, 'index'])->name('templates.index');
        Route::post('/templates', [JobTemplateController::class, 'store'])->name('templates.store');
        Route::get('/templates/{template}', [JobTemplateController::class, 'show'])->name('templates.show');
        Route::get('/templates/{template}/apply', [JobTemplateController::class, 'apply'])->name('templates.apply');
        Route::delete('/templates/{template}', [JobTemplateController::class, 'destroy'])->name('templates.destroy');
    });

    Route::prefix('provider')->name('provider.')->middleware('role:provider')->group(function () {
        Route::get('/services', [ServiceListingController::class, 'index'])->name('services.index');
        Route::get('/services/create', [ServiceListingController::class, 'create'])->name('services.create');
        Route::post('/services', [ServiceListingController::class, 'store'])->name('services.store');
        Route::get('/services/{service}', [ServiceListingController::class, 'show'])->name('services.show');
        Route::post('/services/{service}/publish', [ServiceListingController::class, 'publish'])->name('services.publish');
    });

    Route::prefix('va')->name('va.')->middleware('role:va')->group(function () {
        Route::get('/profile', [VaProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [VaProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/skills', [VaProfileController::class, 'syncSkills'])->name('profile.skills');
        Route::post('/profile/portfolio', [VaProfileController::class, 'storePortfolioItem'])->name('profile.portfolio.store');
        Route::delete('/profile/portfolio/{item}', [VaProfileController::class, 'destroyPortfolioItem'])->name('profile.portfolio.destroy');
        Route::put('/profile/availability', [VaProfileController::class, 'syncAvailability'])->name('profile.availability');

        Route::get('/verification', [VaVerificationController::class, 'index'])->name('verification.index');
        Route::post('/verification', [VaVerificationController::class, 'store'])->name('verification.store');
        Route::get('/verification/{record}', [VaVerificationController::class, 'show'])->name('verification.show');

        Route::get('/jobs/discover', [JobDiscoveryController::class, 'index'])->name('jobs.discover');
        Route::get('/jobs/{job}', [JobDiscoveryController::class, 'show'])->name('jobs.show');
        Route::post('/jobs/{job}/accept', [JobDiscoveryController::class, 'accept'])->name('jobs.accept');
        Route::get('/notification-preferences', [JobDiscoveryController::class, 'notificationPreferences'])->name('notification-preferences.index');
        Route::put('/notification-preferences', [JobDiscoveryController::class, 'updateNotificationPreferences'])->name('notification-preferences.update');

        Route::get('/work', [JobWorkController::class, 'index'])->name('work.index');
        Route::get('/work/{job}', [JobWorkController::class, 'show'])->name('work.show');
        Route::post('/work/{job}/start', [JobWorkController::class, 'startWork'])->name('work.start');
        Route::post('/work/{job}/deliverables', [JobWorkController::class, 'completeDeliverable'])->name('work.deliverables.store');
        Route::post('/work/{job}/evidence', [JobWorkController::class, 'submitEvidence'])->name('work.evidence.store');
        Route::post('/work/{job}/submit', [JobWorkController::class, 'submitForApproval'])->name('work.submit');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:master_admin,staff')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->middleware('permission:users.view')
            ->name('dashboard');

        Route::get('/users', [UserManagementController::class, 'index'])
            ->middleware('permission:users.view')
            ->name('users.index');
        Route::get('/users/{user}', [UserManagementController::class, 'show'])
            ->middleware('permission:users.view')
            ->name('users.show');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])
            ->middleware('permission:users.manage')
            ->name('users.update');

        Route::get('/verifications', [VerificationReviewController::class, 'index'])
            ->middleware('permission:verifications.view')
            ->name('verifications.index');
        Route::get('/verifications/{record}', [VerificationReviewController::class, 'show'])
            ->middleware('permission:verifications.view')
            ->name('verifications.show');
        Route::post('/verifications/{record}/review', [VerificationReviewController::class, 'review'])
            ->middleware('permission:verifications.manage')
            ->name('verifications.review');

        Route::get('/payouts', [PayoutController::class, 'index'])
            ->middleware('permission:withdrawals.view')
            ->name('payouts.index');
        Route::get('/payouts/{withdrawal}', [PayoutController::class, 'show'])
            ->middleware('permission:withdrawals.view')
            ->name('payouts.show');
        Route::post('/payouts/{withdrawal}/process', [PayoutController::class, 'process'])
            ->middleware('permission:withdrawals.manage')
            ->name('payouts.process');

        Route::get('/disputes', [DisputeManagementController::class, 'index'])
            ->middleware('permission:disputes.view')
            ->name('disputes.index');
        Route::get('/disputes/{dispute}', [DisputeManagementController::class, 'show'])
            ->middleware('permission:disputes.view')
            ->name('disputes.show');
        Route::post('/disputes/{dispute}/assign', [DisputeManagementController::class, 'assign'])
            ->middleware('permission:disputes.manage')
            ->name('disputes.assign');
        Route::post('/disputes/{dispute}/resolve', [DisputeManagementController::class, 'resolve'])
            ->middleware('permission:disputes.manage')
            ->name('disputes.resolve');

        Route::get('/settings', [AdminSettingsController::class, 'index'])
            ->middleware('permission:platform_settings.view')
            ->name('settings.index');
        Route::get('/settings/{key}', [AdminSettingsController::class, 'show'])
            ->middleware('permission:platform_settings.view')
            ->name('settings.show');
        Route::put('/settings', [AdminSettingsController::class, 'update'])
            ->middleware('permission:platform_settings.manage')
            ->name('settings.update');
        Route::delete('/settings/{key}', [AdminSettingsController::class, 'destroy'])
            ->middleware('permission:platform_settings.manage')
            ->name('settings.destroy');

        Route::get('/categories', [AdminCategoryController::class, 'index'])
            ->middleware('permission:platform_settings.view')
            ->name('categories.index');
        Route::post('/categories', [AdminCategoryController::class, 'store'])
            ->middleware('permission:platform_settings.manage')
            ->name('categories.store');
        Route::get('/categories/{category}', [AdminCategoryController::class, 'show'])
            ->middleware('permission:platform_settings.view')
            ->name('categories.show');
        Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])
            ->middleware('permission:platform_settings.manage')
            ->name('categories.update');
        Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])
            ->middleware('permission:platform_settings.manage')
            ->name('categories.destroy');

        Route::get('/staff-roles', [StaffRoleController::class, 'index'])
            ->middleware('role:master_admin')
            ->name('staff-roles.index');
        Route::get('/staff-roles/permissions', [StaffRoleController::class, 'permissions'])
            ->middleware('role:master_admin')
            ->name('staff-roles.permissions');
        Route::post('/staff-roles', [StaffRoleController::class, 'store'])
            ->middleware('role:master_admin')
            ->name('staff-roles.store');
        Route::get('/staff-roles/{role}', [StaffRoleController::class, 'show'])
            ->middleware('role:master_admin')
            ->name('staff-roles.show');
        Route::put('/staff-roles/{role}', [StaffRoleController::class, 'update'])
            ->middleware('role:master_admin')
            ->name('staff-roles.update');
        Route::delete('/staff-roles/{role}', [StaffRoleController::class, 'destroy'])
            ->middleware('role:master_admin')
            ->name('staff-roles.destroy');
    });
});
