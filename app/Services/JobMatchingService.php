<?php

namespace App\Services;

use App\Models\MarketplaceJob;
use App\Models\UserSkill;
use App\Models\VaProfile;
use Illuminate\Support\Collection;

class JobMatchingService
{
    public function __construct(
        private readonly PlatformSettingsService $settings,
    ) {}

    /**
     * @return Collection<int, array{profile: VaProfile, score: float, reasons: list<string>}>
     */
    public function matchForJob(MarketplaceJob $job, int $limit = 20): Collection
    {
        $job->loadMissing(['jobSkills.skill', 'category']);

        $requiredSkillIds = $job->jobSkills
            ->where('is_required', true)
            ->pluck('skill_id');

        $optionalSkillIds = $job->jobSkills
            ->where('is_required', false)
            ->pluck('skill_id');

        return VaProfile::query()
            ->with('user')
            ->where('is_verified', true)
            ->where('status', 'approved')
            ->where('availability_status', 'available')
            ->get()
            ->map(function (VaProfile $profile) use ($job, $requiredSkillIds, $optionalSkillIds) {
                return $this->scoreProfile($profile, $job, $requiredSkillIds, $optionalSkillIds);
            })
            ->filter(fn (array $result) => $result['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /**
     * @param  Collection<int, int>  $requiredSkillIds
     * @param  Collection<int, int>  $optionalSkillIds
     * @return array{profile: VaProfile, score: float, reasons: list<string>}
     */
    private function scoreProfile(
        VaProfile $profile,
        MarketplaceJob $job,
        Collection $requiredSkillIds,
        Collection $optionalSkillIds,
    ): array {
        $score = 0.0;
        $reasons = [];

        $userSkillIds = UserSkill::query()
            ->where('user_id', $profile->user_id)
            ->pluck('skill_id');

        if ($job->category_id !== null) {
            $categorySkillIds = $userSkillIds->intersect(
                $job->jobSkills->pluck('skill_id'),
            );

            if ($categorySkillIds->isNotEmpty()) {
                $score += (float) $this->settings->get('matching.category_weight', 40);
                $reasons[] = 'Category-aligned skills';
            }
        }

        if ($requiredSkillIds->isNotEmpty()) {
            $matchedRequired = $requiredSkillIds->intersect($userSkillIds);
            $requiredRatio = $matchedRequired->count() / max($requiredSkillIds->count(), 1);
            $requiredScore = (float) $this->settings->get('matching.skill_weight', 50) * $requiredRatio;

            if ($requiredScore > 0) {
                $score += $requiredScore;
                $reasons[] = sprintf(
                    'Matched %d/%d required skills',
                    $matchedRequired->count(),
                    $requiredSkillIds->count(),
                );
            } elseif ($requiredSkillIds->isNotEmpty()) {
                return [
                    'profile' => $profile,
                    'score' => 0.0,
                    'reasons' => ['Missing required skills'],
                ];
            }
        }

        if ($optionalSkillIds->isNotEmpty()) {
            $matchedOptional = $optionalSkillIds->intersect($userSkillIds);
            $optionalScore = (float) $this->settings->get('matching.optional_skill_weight', 10)
                * ($matchedOptional->count() / max($optionalSkillIds->count(), 1));

            if ($optionalScore > 0) {
                $score += $optionalScore;
                $reasons[] = sprintf('Matched %d optional skills', $matchedOptional->count());
            }
        }

        if ($this->isAvailableForJob($profile, $job)) {
            $score += (float) $this->settings->get('matching.availability_weight', 10);
            $reasons[] = 'Available during requested window';
        } else {
            return [
                'profile' => $profile,
                'score' => 0.0,
                'reasons' => ['Unavailable for job window'],
            ];
        }

        return [
            'profile' => $profile,
            'score' => round($score, 2),
            'reasons' => $reasons,
        ];
    }

    private function isAvailableForJob(VaProfile $profile, MarketplaceJob $job): bool
    {
        if (! $profile->isAvailable()) {
            return false;
        }

        if ($job->starts_at === null && $job->deadline_at === null) {
            return true;
        }

        return $profile->availability_status === 'available';
    }
}
