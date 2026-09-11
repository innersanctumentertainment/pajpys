<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\JobMessage;
use App\Models\MarketplaceJob;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $reports = Report::query()
            ->where('reporter_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($reports);
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        $data = $request->validated();
        $reportable = $this->resolveReportable($data['reportable_type'], $data['reportable_id']);

        $report = Report::query()->create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $reportable->getMorphClass(),
            'reportable_id' => $reportable->getKey(),
            'reason_code' => $data['reason_code'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
        ]);

        $this->auditLog->log(
            'report.created',
            $report,
            newValues: ['reason_code' => $data['reason_code']],
            user: $request->user(),
        );

        return response()->json(['report' => $report], 201);
    }

    private function resolveReportable(string $type, int $id): User|MarketplaceJob|Review|JobMessage
    {
        return match ($type) {
            'user' => User::query()->findOrFail($id),
            'marketplace_job' => MarketplaceJob::query()->findOrFail($id),
            'review' => Review::query()->findOrFail($id),
            'message' => JobMessage::query()->findOrFail($id),
            default => abort(422, 'Invalid reportable type.'),
        };
    }
}
