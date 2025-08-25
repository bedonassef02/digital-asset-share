<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Model;

class NotificationService
{
    public function create(
        int $userId,
        string $type,
        string $message,
        Model $notifiable = null
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'notifiable_type' => $notifiable ? $notifiable->getMorphClass() : null,
            'notifiable_id' => $notifiable ? $notifiable->id : null,
        ]);
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->update(['read_at' => now()]);
    }

    public function getUnread(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->latest()
            ->get();
    }

    public function getAll(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return Notification::where('user_id', $userId)
            ->latest()
            ->get();
    }
}