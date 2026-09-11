<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreJobTemplateRequest;
use App\Models\JobTemplate;
use App\Models\JobTemplateSkill;
use App\Models\MarketplaceJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $templates = JobTemplate::query()
            ->where(function ($query) use ($request) {
                $query->where('user_id', $request->user()->id)
                    ->orWhere('is_public', true);
            })
            ->with(['category', 'templateSkills.skill'])
            ->latest()
            ->paginate(20);

        return response()->json($templates);
    }

    public function show(Request $request, JobTemplate $template): JsonResponse
    {
        if ($template->user_id !== $request->user()->id && ! $template->is_public) {
            abort(403);
        }

        return response()->json(['template' => $template->load(['category', 'templateSkills.skill'])]);
    }

    public function store(StoreJobTemplateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if (isset($data['marketplace_job_id'])) {
            $job = MarketplaceJob::query()
                ->where('client_id', $user->id)
                ->findOrFail($data['marketplace_job_id']);

            $template = $this->createFromJob($job, $data['title'], $user->id, $data['is_public'] ?? false);
        } else {
            $template = JobTemplate::query()->create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'requirements' => $data['requirements'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'job_type' => $data['job_type'] ?? 'fixed',
                'default_budget_amount' => $data['default_budget_amount'] ?? null,
                'default_budget_amount_minor' => isset($data['default_budget_amount'])
                    ? (int) round((float) $data['default_budget_amount'] * 100)
                    : null,
                'currency' => $data['currency'] ?? 'TTD',
                'is_public' => $data['is_public'] ?? false,
            ]);

            foreach ($data['skill_ids'] ?? [] as $skillId) {
                JobTemplateSkill::query()->create([
                    'job_template_id' => $template->id,
                    'skill_id' => $skillId,
                    'is_required' => true,
                ]);
            }
        }

        return response()->json(['template' => $template->load('templateSkills.skill')], 201);
    }

    public function apply(Request $request, JobTemplate $template): JsonResponse
    {
        if ($template->user_id !== $request->user()->id && ! $template->is_public) {
            abort(403);
        }

        $template->load('templateSkills');

        return response()->json([
            'prefill' => [
                'title' => $template->title,
                'description' => $template->description,
                'requirements' => $template->requirements,
                'category_id' => $template->category_id,
                'job_type' => $template->job_type,
                'budget_amount' => $template->default_budget_amount,
                'currency' => $template->currency,
                'skill_ids' => $template->templateSkills->pluck('skill_id'),
                'required_skill_ids' => $template->templateSkills->where('is_required', true)->pluck('skill_id'),
            ],
        ]);
    }

    public function destroy(Request $request, JobTemplate $template): JsonResponse
    {
        if ($template->user_id !== $request->user()->id) {
            abort(403);
        }

        $template->delete();

        return response()->json(['message' => 'Template deleted.']);
    }

    private function createFromJob(
        MarketplaceJob $job,
        string $title,
        int $userId,
        bool $isPublic,
    ): JobTemplate {
        $job->load('jobSkills');

        $template = JobTemplate::query()->create([
            'user_id' => $userId,
            'category_id' => $job->category_id,
            'title' => $title,
            'description' => $job->description,
            'requirements' => $job->requirements,
            'job_type' => $job->job_type,
            'default_budget_amount' => $job->budget_amount,
            'default_budget_amount_minor' => $job->budget_amount_minor,
            'currency' => $job->currency,
            'is_public' => $isPublic,
        ]);

        foreach ($job->jobSkills as $jobSkill) {
            JobTemplateSkill::query()->create([
                'job_template_id' => $template->id,
                'skill_id' => $jobSkill->skill_id,
                'is_required' => $jobSkill->is_required,
            ]);
        }

        return $template;
    }
}
