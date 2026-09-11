<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\MarketplaceNotification;
use Illuminate\Notifications\DatabaseNotification;

class NotificationService
{
    public function notify(
        User $user,
        string $type,
        string $title,
        string $body,
        array $data = [],
        bool $sendEmail = true,
    ): DatabaseNotification {
        $user->notify(new MarketplaceNotification(
            title: $title,
            body: $body,
            data: array_merge($data, ['notification_type' => $type]),
            sendEmail: $sendEmail,
        ));

        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->latest()->first();

        return $notification;
    }

    public function markRead(DatabaseNotification $notification): DatabaseNotification
    {
        $notification->markAsRead();

        return $notification->fresh();
    }

    public function markAllRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
