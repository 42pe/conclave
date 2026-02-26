<?php

namespace App\Notifications;

use App\Models\Discussion;
use App\Models\Reply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BookmarkActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Reply $reply,
        private Discussion $discussion,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'bookmark_activity',
            'discussion_id' => $this->discussion->id,
            'discussion_title' => $this->discussion->title,
            'discussion_slug' => $this->discussion->slug,
            'topic_id' => $this->discussion->topic_id,
            'topic_slug' => $this->discussion->topic?->slug,
            'replier_name' => $this->reply->user?->display_name ?? 'Someone',
            'replier_username' => $this->reply->user?->username,
        ];
    }
}
