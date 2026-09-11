<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AuthorizesMarketplaceJob;
use App\Http\Requests\SendMessageRequest;
use App\Models\JobMessage;
use App\Models\MarketplaceJob;
use App\Services\AuditLogService;
use App\Services\ContactDetectionService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    use AuthorizesMarketplaceJob;

    public function __construct(
        private readonly ContactDetectionService $contactDetection,
        private readonly NotificationService $notifications,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request, MarketplaceJob $job): JsonResponse|View
    {
        $this->authorizeJobParticipant($request->user(), $job);

        $messages = $job->messages()
            ->with(['sender', 'recipient'])
            ->oldest()
            ->paginate(50);

        if ($request->wantsJson()) {
            return response()->json($messages);
        }

        return view('messages.index', compact('job', 'messages'));
    }

    public function store(SendMessageRequest $request, MarketplaceJob $job): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $this->authorizeJobParticipant($user, $job);

        $body = $request->validated('body');
        $contactAnalysis = $this->contactDetection->analyze($body);

        $recipientId = $request->validated('recipient_id')
            ?? ($job->client_id === $user->id
                ? $job->assignments()->where('status', 'active')->value('va_id')
                : $job->client_id);

        $message = JobMessage::query()->create([
            'marketplace_job_id' => $job->id,
            'sender_id' => $user->id,
            'recipient_id' => $recipientId,
            'body' => $body,
        ]);

        if ($contactAnalysis['has_contact_info']) {
            $this->auditLog->log(
                'message.contact_info_detected',
                $message,
                newValues: ['warnings' => $contactAnalysis['warnings']],
                user: $user,
            );
        }

        if ($recipientId !== null) {
            $recipient = \App\Models\User::query()->find($recipientId);
            if ($recipient !== null) {
                $this->notifications->notify(
                    $recipient,
                    'message.received',
                    'New message',
                    "You have a new message on job \"{$job->title}\".",
                    ['marketplace_job_id' => $job->id, 'message_id' => $message->id],
                    sendEmail: false,
                );
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message->load(['sender', 'recipient']),
                'contact_warnings' => $contactAnalysis['warnings'],
            ], 201);
        }

        $redirect = redirect()->route('jobs.messages.index', $job);

        if ($contactAnalysis['warnings'] !== []) {
            $redirect->with('error', 'Message sent, but it may contain contact information that violates platform policy.');
        } else {
            $redirect->with('success', 'Message sent.');
        }

        return $redirect;
    }

    public function markRead(Request $request, MarketplaceJob $job, JobMessage $message): JsonResponse|RedirectResponse
    {
        $this->authorizeJobParticipant($request->user(), $job);

        if ($message->marketplace_job_id !== $job->id) {
            abort(404);
        }

        if ($message->recipient_id === $request->user()->id) {
            $message->update(['read_at' => now()]);
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => $message->fresh()]);
        }

        return back();
    }
}
