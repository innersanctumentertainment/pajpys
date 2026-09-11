<?php

namespace App\Http\Controllers\Va;

use App\Http\Controllers\Controller;
use App\Http\Requests\Va\SubmitVerificationRequest;
use App\Models\VerificationDocument;
use App\Models\VerificationRecord;
use App\Services\AuditLogService;
use App\Services\FileStorageService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __construct(
        private readonly FileStorageService $fileStorage,
        private readonly AuditLogService $auditLog,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $records = VerificationRecord::query()
            ->where('user_id', $request->user()->id)
            ->with('documents')
            ->latest()
            ->get();

        return response()->json(['records' => $records]);
    }

    public function store(SubmitVerificationRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $record = VerificationRecord::query()->create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'status' => 'pending',
        ]);

        foreach ($data['documents'] as $document) {
            $file = $document['file'];
            $stored = $this->fileStorage->storePrivate($file, $user);

            VerificationDocument::query()->create([
                'verification_record_id' => $record->id,
                'document_type' => $document['document_type'],
                'file_path' => $stored->path,
                'original_filename' => $stored->original_name,
                'mime_type' => $stored->mime_type,
                'file_size' => $stored->size,
                'status' => 'pending',
            ]);
        }

        $this->auditLog->log(
            'va.verification_submitted',
            $record,
            newValues: ['type' => $record->type],
            user: $user,
        );

        return response()->json(['record' => $record->load('documents')], 201);
    }

    public function show(Request $request, VerificationRecord $record): JsonResponse
    {
        if ($record->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['record' => $record->load('documents')]);
    }
}
