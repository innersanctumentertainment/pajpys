<?php

namespace App\Http\Controllers;

use App\Enums\JobStatus;
use App\Http\Concerns\AuthorizesMarketplaceJob;
use App\Http\Requests\StoreReviewRequest;
use App\Models\MarketplaceJob;
use App\Models\Review;
use App\Models\VaProfile;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use AuthorizesMarketplaceJob;

    public function __construct(
        private readonly NotificationService $notifications,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request, MarketplaceJob $job): JsonResponse
    {
        $this->authorizeJobParticipant($request->user(), $job);

        $reviews = $job->reviews()
            ->with(['reviewer', 'reviewee'])
            ->where('is_public', true)
            ->get();

        return response()->json(['reviews' => $reviews]);
    }

    public function store(StoreReviewRequest $request, MarketplaceJob $job): JsonResponse
    {
        $user = $request->user();
        $this->authorizeJobParticipant($user, $job);

        if ($job->status !== JobStatus::Completed) {
            return response()->json(['message' => 'Reviews can only be submitted for completed jobs.'], 422);
        }

        $revieweeId = $request->validated('reviewee_id');

        if ($revieweeId === $user->id) {
            return response()->json(['message' => 'You cannot review yourself.'], 422);
        }

        $review = Review::query()->updateOrCreate(
            [
                'marketplace_job_id' => $job->id,
                'reviewer_id' => $user->id,
            ],
            [
                'reviewee_id' => $revieweeId,
                'rating' => $request->validated('rating'),
                'comment' => $request->validated('comment'),
                'is_public' => $request->validated('is_public') ?? true,
            ],
        );

        if ($review->reviewee->isVa()) {
            $this->updateVaRating($review->reviewee_id);
        }

        $this->notifications->notify(
            $review->reviewee,
            'review.received',
            'New review',
            'You received a new review on a completed job.',
            ['marketplace_job_id' => $job->id, 'rating' => $review->rating],
        );

        $this->auditLog->log('review.created', $review, user: $user);

        return response()->json(['review' => $review->load(['reviewer', 'reviewee'])], 201);
    }

    private function updateVaRating(int $userId): void
    {
        $profile = VaProfile::query()->where('user_id', $userId)->first();

        if ($profile === null) {
            return;
        }

        $average = Review::query()
            ->where('reviewee_id', $userId)
            ->avg('rating');

        $profile->update(['average_rating' => round((float) $average, 2)]);
    }
}
