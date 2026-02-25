<?php

namespace App\Notifications;

use App\Models\Discussion;
use App\Models\Reply;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private User $mentioner,
        private Discussion $discussion,
        private ?Reply $reply = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (! $notifiable->notify_mentions) {
            return [];
        }

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mentionerName = $this->mentioner->display_name;

        return (new MailMessage)
            ->subject("{$mentionerName} mentioned you in \"{$this->discussion->title}\"")
            ->greeting("Hello {$notifiable->display_name}!")
            ->line("{$mentionerName} mentioned you in the discussion \"{$this->discussion->title}\".")
            ->action('View Discussion', url(route('topics.discussions.show', [
                $this->discussion->topic_id,
                $this->discussion,
            ])));
    }
}
