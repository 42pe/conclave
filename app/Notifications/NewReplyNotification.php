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
        public Reply $reply,
        public Discussion $discussion,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (! $notifiable->notify_replies) {
            return [];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $replierName = $this->reply->user?->display_name ?? 'Someone';

        return (new MailMessage)
            ->subject("New reply on \"{$this->discussion->title}\"")
            ->greeting("Hello {$notifiable->display_name}!")
            ->line("{$replierName} replied to the discussion \"{$this->discussion->title}\".")
            ->action('View Discussion', url("/topics/{$this->discussion->topic_id}/discussions/{$this->discussion->slug}"))
            ->line('You can manage your notification preferences in your account settings.');
    }
}
