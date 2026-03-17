<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Support\Facades\Auth;

class ShareLinkAppNotification extends Notification
{
    use Queueable;
     protected $sharelink;

    /**
     * Create a new notification instance.
     */
    public function __construct($sharelink)
    {
         $this->sharelink = $sharelink;
    }

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
     * Get the mail representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'sharelink_id' => $this->sharelink->id,
            'sharelink_name' => $this->sharelink->name,
            'workspace_id' => $this->sharelink->workspace_id,
            "type"=>$this->sharelink->type,
            "title"=>$this->sharelink->title,
            'message' => "Sharelink '{$this->sharelink->name}' was viewed."
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
