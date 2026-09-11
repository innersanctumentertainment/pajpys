<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewVerificationRequest;
use App\Models\VaProfile;
use App\Models\VerificationRecord;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationReviewController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $records = VerificationRecord::query()
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->with(['user.vaProfile', 'documents'])
            ->latest()
            ->paginate(30);

        return response()->json($records);
    }

    public function show(VerificationRecord $record): JsonResponse
    {
        return response()->json(['record' => $record->load(['user', 'documents'])]);
    }

    public function review(ReviewVerificationRequest $request, VerificationRecord $record): JsonResponse
    {
        $admin = $request->user();
        $status = $request->validated('status');

        $record->update([
            'status' => $status,
            'notes' => $request->validated('notes'),
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $record->documents()->update(['status' => $status]);

        if ($status === 'approved') {
            VaProfile::query()->updateOrCreate(
                ['user_id' => $record->user_id],
                ['is_verified' => true, 'status' => 'approved', 'approved_at' => now()],
            );
        }

        $this->notifications->notify(
            $record->user,
            'verification.reviewed',
            'Verification reviewed',
            "Your {$record->type} verification was {$status}.",
            ['verification_record_id' => $record->id, 'status' => $status],
        );

        $this->auditLog->log(
            'admin.verification_reviewed',
            $record,
            newValues: ['status' => $status],
            user: $admin,
        );

        return response()->json(['record' => $record->fresh()->load('documents')]);
    }
}
