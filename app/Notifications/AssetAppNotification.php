<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Support\Facades\Auth;

class AssetAppNotification extends Notification
{
    use Queueable;
     protected $asset;

    /**
     * Create a new notification instance.
     */
    public function __construct($asset)
    {
         $this->asset = $asset;
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
            'sharelink_id' => $this->asset->id,
            'sharelink_name' => $this->asset->name,
            "type"=>$this->asset->type,
            "title"=>$this->asset->title,
            'message' => "Sharelink '{$this->asset->name}' was viewed."
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
