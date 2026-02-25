<?php

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Message $message,
        private Conversation $conversation,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable->notify_messages ? ['database', 'mail'] : ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_message',
            'conversation_id' => $this->conversation->id,
            'message_id' => $this->message->id,
            'sender_name' => $this->message->user?->display_name ?? 'Someone',
            'sender_username' => $this->message->user?->username,
            'sender_avatar' => $this->message->user?->avatar_path,
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $senderName = $this->message->user?->display_name ?? 'Someone';

        return (new MailMessage)
            ->subject('New message from '.$senderName)
            ->greeting("Hello {$notifiable->display_name}!")
            ->line("{$senderName} sent you a new message.")
            ->action('View Conversation', url(route('conversations.show', $this->conversation)));
    }
}
