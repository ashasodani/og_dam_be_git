<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Support\Facades\Auth;

class CollectionUpdatedAppNotification extends Notification
{
    use Queueable;
     protected $collection;

    /**
     * Create a new notification instance.
     */
    public function __construct($collection)
    {
         $this->collection = $collection;
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
            'collection_id' => $this->collection->id,
            'user_id' => Auth::user()->id,
            'user_name' => Auth::user()->name,
            'collection_name' => $this->collection->name,
            'workspace_id' => $this->collection->workspace_id,
            "title"=>"Collection Viewed",
            'message' => "Collection '{$this->collection->name}' was updated."
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
