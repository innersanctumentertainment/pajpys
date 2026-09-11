<?php

namespace App\Http\Controllers\Va;

use App\Http\Controllers\Controller;
use App\Http\Requests\Va\StorePortfolioItemRequest;
use App\Http\Requests\Va\SyncAvailabilityRequest;
use App\Http\Requests\Va\SyncVaSkillsRequest;
use App\Http\Requests\Va\UpdateVaProfileRequest;
use App\Models\AvailabilitySchedule;
use App\Models\PortfolioItem;
use App\Models\UserSkill;
use App\Models\VaProfile;
use App\Services\AuditLogService;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly FileStorageService $fileStorage,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $profile = $this->resolveProfile($request);

        return response()->json([
            'profile' => $profile->load(['portfolioItems', 'availabilitySchedules']),
            'skills' => $request->user()->skills()->with('skill')->get(),
        ]);
    }

    public function update(UpdateVaProfileRequest $request): JsonResponse
    {
        $profile = $this->resolveProfile($request);
        $profile->update($request->validated());

        $this->auditLog->log('va.profile_updated', $profile, user: $request->user());

        return response()->json(['profile' => $profile->fresh()]);
    }

    public function syncSkills(SyncVaSkillsRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->skills()->delete();

        foreach ($request->validated('skills') as $skill) {
            UserSkill::query()->create([
                'user_id' => $user->id,
                'skill_id' => $skill['skill_id'],
                'proficiency_level' => $skill['proficiency_level'] ?? 'intermediate',
                'years_experience' => $skill['years_experience'] ?? null,
            ]);
        }

        return response()->json(['skills' => $user->skills()->with('skill')->get()]);
    }

    public function storePortfolioItem(StorePortfolioItemRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $mediaPath = null;
        $mediaType = null;

        if ($request->hasFile('media')) {
            $stored = $this->fileStorage->storePrivate($request->file('media'), $user);
            $mediaPath = $stored->path;
            $mediaType = $stored->mime_type;
        }

        $item = PortfolioItem::query()->create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'media_path' => $mediaPath,
            'media_type' => $mediaType,
            'external_url' => $data['external_url'] ?? null,
            'is_public' => $data['is_public'] ?? true,
        ]);

        return response()->json(['item' => $item], 201);
    }

    public function destroyPortfolioItem(Request $request, PortfolioItem $item): JsonResponse
    {
        if ($item->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($item->media_path !== null) {
            Storage::disk('local')->delete($item->media_path);
        }

        $item->delete();

        return response()->json(['message' => 'Portfolio item deleted.']);
    }

    public function syncAvailability(SyncAvailabilityRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->vaProfile?->availabilitySchedules()->delete();

        $schedules = [];
        foreach ($request->validated('schedules') as $schedule) {
            $schedules[] = AvailabilitySchedule::query()->create([
                'user_id' => $user->id,
                'day_of_week' => $schedule['day_of_week'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'timezone' => $schedule['timezone'] ?? $user->vaProfile?->timezone ?? 'America/Port_of_Spain',
                'is_available' => $schedule['is_available'] ?? true,
            ]);
        }

        return response()->json(['schedules' => $schedules]);
    }

    private function resolveProfile(Request $request): VaProfile
    {
        return VaProfile::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['status' => 'pending', 'availability_status' => 'available'],
        );
    }
}
