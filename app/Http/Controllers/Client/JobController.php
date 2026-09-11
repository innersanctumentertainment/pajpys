<?php

namespace App\Http\Controllers\Client;

use App\Enums\JobStatus;
use App\Http\Concerns\AuthorizesMarketplaceJob;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreJobBasicInfoRequest;
use App\Http\Requests\Client\UpdateJobDeadlineRequest;
use App\Http\Requests\Client\UpdateJobPriceRequest;
use App\Http\Requests\Client\UpdateJobVisibilityRequest;
use App\Models\Category;
use App\Models\JobSkill;
use App\Models\MarketplaceJob;
use App\Models\Skill;
use App\Services\AuditLogService;
use App\Services\JobMatchingService;
use App\Services\JobWorkflowService;
use App\Services\NotificationService;
use App\Services\PlatformSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class JobController extends Controller
{
    use AuthorizesMarketplaceJob;

    public function __construct(
        private readonly JobWorkflowService $workflow,
        private readonly JobMatchingService $matching,
        private readonly AuditLogService $auditLog,
        private readonly NotificationService $notifications,
        private readonly PlatformSettingsService $settings,
    ) {}

    public function index(Request $request): JsonResponse|View
    {
        $jobs = MarketplaceJob::query()
            ->where('client_id', $request->user()->id)
            ->with(['category', 'jobSkills.skill'])
            ->latest()
            ->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($jobs);
        }

        return view('client.jobs.index', compact('jobs'));
    }

    public function show(Request $request, MarketplaceJob $job): JsonResponse|View
    {
        $this->authorizeClientJob($request->user(), $job);

        $job->load(['category', 'jobSkills.skill', 'candidates.va', 'assignments.va']);

        if ($request->wantsJson()) {
            return response()->json([
                'job' => $job,
                'available_transitions' => $this->workflow->availableTransitions($job),
                'wizard_step' => $this->resolveWizardStep($job),
            ]);
        }

        return view('client.jobs.show', $this->showViewData($job));
    }

    public function store(StoreJobBasicInfoRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $job = MarketplaceJob::query()->create([
            'client_id' => $user->id,
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'description' => $data['description'],
            'requirements' => $data['requirements'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'job_type' => $data['job_type'],
            'status' => JobStatus::Draft,
            'currency' => $this->settings->get('default_currency', 'TTD'),
        ]);

        $this->syncJobSkills($job, $data['skill_ids'] ?? [], $data['required_skill_ids'] ?? []);

        $this->auditLog->log('job.created', $job, newValues: ['title' => $job->title], user: $user);

        if ($request->wantsJson()) {
            return response()->json(['job' => $job->load('jobSkills.skill')], 201);
        }

        return redirect()
            ->route('client.jobs.show', $job)
            ->with('success', 'Job draft created. Continue with the setup wizard.');
    }

    public function updateBasic(StoreJobBasicInfoRequest $request, MarketplaceJob $job): JsonResponse|RedirectResponse
    {
        $this->authorizeClientJob($request->user(), $job);
        $this->ensureDraftOrEditable($job);

        $data = $request->validated();

        $job->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'requirements' => $data['requirements'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'job_type' => $data['job_type'],
        ]);

        $this->syncJobSkills($job, $data['skill_ids'] ?? [], $data['required_skill_ids'] ?? []);

        if ($request->wantsJson()) {
            return response()->json(['job' => $job->fresh()->load('jobSkills.skill')]);
        }

        return redirect()
            ->route('client.jobs.show', $job)
            ->with('success', 'Basic information saved.');
    }

    public function updatePrice(UpdateJobPriceRequest $request, MarketplaceJob $job): JsonResponse|RedirectResponse
    {
        $this->authorizeClientJob($request->user(), $job);
        $this->ensureDraftOrEditable($job);

        $data = $request->validated();
        $currency = strtoupper($data['currency'] ?? $job->currency ?? 'TTD');

        $budgetAmount = $data['budget_amount'] ?? null;
        $budgetMinor = $budgetAmount !== null ? (int) round((float) $budgetAmount * 100) : null;

        $minPrice = (float) $this->settings->get('minimum_job_price', 30);
        if ($budgetAmount !== null && $budgetAmount < $minPrice) {
            if ($request->wantsJson()) {
                return response()->json(['message' => "Minimum job price is {$minPrice} {$currency}."], 422);
            }

            return back()->with('error', "Minimum job price is {$minPrice} {$currency}.");
        }

        $job->update([
            'budget_amount' => $budgetAmount,
            'budget_amount_minor' => $budgetMinor,
            'hourly_rate' => $data['hourly_rate'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? null,
            'currency' => $currency,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['job' => $job->fresh()]);
        }

        return redirect()
            ->route('client.jobs.show', $job)
            ->with('success', 'Pricing saved.');
    }

    public function updateDeadline(UpdateJobDeadlineRequest $request, MarketplaceJob $job): JsonResponse|RedirectResponse
    {
        $this->authorizeClientJob($request->user(), $job);
        $this->ensureDraftOrEditable($job);

        $job->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json(['job' => $job->fresh()]);
        }

        return redirect()
            ->route('client.jobs.show', $job)
            ->with('success', 'Schedule saved.');
    }

    public function updateVisibility(UpdateJobVisibilityRequest $request, MarketplaceJob $job): JsonResponse|RedirectResponse
    {
        $this->authorizeClientJob($request->user(), $job);
        $this->ensureDraftOrEditable($job);

        $job->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json(['job' => $job->fresh()]);
        }

        return redirect()
            ->route('client.jobs.show', $job)
            ->with('success', 'Visibility settings saved.');
    }

    public function review(Request $request, MarketplaceJob $job): JsonResponse|View
    {
        $this->authorizeClientJob($request->user(), $job);

        if ($request->wantsJson()) {
            return response()->json([
                'job' => $job->load(['category', 'jobSkills.skill']),
                'wizard_step' => 'review',
                'validation_errors' => $this->validateForPublish($job),
            ]);
        }

        return view('client.jobs.show', array_merge($this->showViewData($job), [
            'wizardStep' => 'review',
            'validationErrors' => $this->validateForPublish($job),
        ]));
    }

    public function publish(Request $request, MarketplaceJob $job): JsonResponse|RedirectResponse
    {
        $this->authorizeClientJob($request->user(), $job);

        $errors = $this->validateForPublish($job);
        if ($errors !== []) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Job is incomplete.', 'errors' => $errors], 422);
            }

            return redirect()
                ->route('client.jobs.wizard.review', $job)
                ->with('error', 'Job is incomplete. Please fix the issues before publishing.');
        }

        $job = $this->workflow->publish($job, $request->user());

        $this->notifications->notify(
            $request->user(),
            'job.published',
            'Job submitted for funding',
            "Your job \"{$job->title}\" is ready for payment.",
            ['marketplace_job_id' => $job->id],
        );

        if ($request->wantsJson()) {
            return response()->json(['job' => $job]);
        }

        return redirect()
            ->route('client.jobs.posting-fee', $job)
            ->with('success', 'Pay the '.$this->settings->formatMoney(
                (float) $this->settings->get('job_posting_fee', 20),
                (string) $this->settings->get('job_posting_fee_currency', 'TTD'),
            ).' posting fee to continue.');
    }

    public function goLive(Request $request, MarketplaceJob $job): JsonResponse|RedirectResponse
    {
        $this->authorizeClientJob($request->user(), $job);

        if ($job->status !== JobStatus::Open) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Job must be funded before going live.'], 422);
            }

            return back()->with('error', 'Job must be funded before going live.');
        }

        $job = $this->workflow->openForAcceptance($job, $request->user());
        $matches = $this->matching->matchForJob($job);

        foreach ($matches->take(10) as $match) {
            $this->notifications->notify(
                $match['profile']->user,
                'job.match',
                'New job match',
                "A new job \"{$job->title}\" matches your profile.",
                ['marketplace_job_id' => $job->id, 'score' => $match['score']],
            );
        }

        if ($request->wantsJson()) {
            return response()->json(['job' => $job, 'matched_vas' => $matches->count()]);
        }

        return redirect()
            ->route('client.jobs.show', $job)
            ->with('success', 'Job is now live and accepting applications.');
    }

    public function destroy(Request $request, MarketplaceJob $job): JsonResponse|RedirectResponse
    {
        $this->authorizeClientJob($request->user(), $job);

        if (! in_array($job->status, [JobStatus::Draft, JobStatus::PendingPayment, JobStatus::Cancelled], true)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Only draft or cancelled jobs can be deleted.'], 422);
            }

            return back()->with('error', 'Only draft or cancelled jobs can be deleted.');
        }

        $this->auditLog->log('job.deleted', $job, user: $request->user());
        $job->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Job deleted.']);
        }

        return redirect()
            ->route('client.jobs.index')
            ->with('success', 'Job deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function showViewData(MarketplaceJob $job): array
    {
        return [
            'job' => $job,
            'availableTransitions' => $this->workflow->availableTransitions($job),
            'wizardStep' => $this->resolveWizardStep($job),
            'validationErrors' => [],
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'skills' => Skill::query()->where('is_active', true)->orderBy('name')->get(),
            'defaultCurrency' => $this->settings->defaultCurrency(),
            'allowedCurrencies' => $this->settings->allowedCurrencies(),
        ];
    }

    /**
     * @param  list<int>  $skillIds
     * @param  list<int>  $requiredSkillIds
     */
    private function syncJobSkills(MarketplaceJob $job, array $skillIds, array $requiredSkillIds): void
    {
        $job->jobSkills()->delete();

        foreach (array_unique($skillIds) as $skillId) {
            JobSkill::query()->create([
                'marketplace_job_id' => $job->id,
                'skill_id' => $skillId,
                'is_required' => in_array($skillId, $requiredSkillIds, true),
            ]);
        }
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 1;

        while (MarketplaceJob::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function ensureDraftOrEditable(MarketplaceJob $job): void
    {
        if (! in_array($job->status, [JobStatus::Draft, JobStatus::PendingPayment], true)) {
            abort(422, 'Job can only be edited while in draft or pending payment.');
        }
    }

    /**
     * @return list<string>
     */
    private function validateForPublish(MarketplaceJob $job): array
    {
        $errors = [];

        if ($job->budget_amount === null && $job->hourly_rate === null) {
            $errors[] = 'Price or hourly rate is required.';
        }

        if ($job->deadline_at === null) {
            $errors[] = 'Deadline is required.';
        }

        return $errors;
    }

    private function resolveWizardStep(MarketplaceJob $job): string
    {
        if ($job->title === '' || $job->description === '') {
            return 'basic';
        }

        if ($job->budget_amount === null && $job->hourly_rate === null) {
            return 'price';
        }

        if ($job->deadline_at === null) {
            return 'deadline';
        }

        if ($job->visibility === null) {
            return 'visibility';
        }

        return 'review';
    }
}
