<?php

namespace App\Notifications;

use App\Models\Discussion;
use App\Models\Reply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReplyNotification extends Notification implements ShouldQueue
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
        if (! $notifiable->notify_replies) {
            return [];
        }

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $replierName = $this->reply->user?->display_name ?? 'Someone';

        return (new MailMessage)
            ->subject('New reply in "'.$this->discussion->title.'"')
            ->greeting("Hello {$notifiable->display_name}!")
            ->line("{$replierName} replied in the discussion \"{$this->discussion->title}\".")
            ->action('View Discussion', url(route('topics.discussions.show', [
                $this->discussion->topic_id,
                $this->discussion,
            ])));
    }
}
