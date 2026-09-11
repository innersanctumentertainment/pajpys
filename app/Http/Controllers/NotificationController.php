<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse|View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(30);

        if ($request->wantsJson()) {
            return response()->json($notifications);
        }

        return view('notifications.index', compact('notifications'));
    }

    public function unread(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();

        return response()->json(['unread_count' => $count]);
    }

    public function markRead(Request $request, string $notificationId): JsonResponse|RedirectResponse
    {
        /** @var DatabaseNotification|null $notification */
        $notification = $request->user()
            ->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        $this->notificationService->markRead($notification);

        if ($request->wantsJson()) {
            return response()->json(['notification' => $notification->fresh()]);
        }

        return back();
    }

    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        $count = $this->notificationService->markAllRead($request->user());

        if ($request->wantsJson()) {
            return response()->json(['marked_read' => $count]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }
}
