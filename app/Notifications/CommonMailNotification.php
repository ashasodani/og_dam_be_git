<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommonMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $message;
    protected $url;
    protected $type;
    protected $user;
    protected $nameOfObject;
     protected $loginUser;
     protected $asset;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $url = null, $type = 'info', $user = null,$loginUser=null,$nameOfObject=null,$asset=[])
    {
        $this->title   = $title;
        $this->message = $message;
        $this->url     = $url;
        $this->type    = $type;
        $this->user    = $user;
        $this->loginUser = $loginUser;
        $this->nameOfObject = $nameOfObject;
        $this->asset = $asset;
        $this->onQueue('mails');
    }

     /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
         switch ($this->type) {
            case 'asset_add_to_share_link':
                $view = 'mail.notification.asset-added-sharelink-notification';
                break;
            case 'asset_removed_from_share_link':
                $view = 'mail.notification.asset-removed-sharelink-notification';
                break;
            case 'share_link_viewed':
                $view = 'mail.notification.sharelink-viewed-notification';
                break;
            case 'asset_update':
                $view = 'mail.notification.asset-update-notification';
                break;
            case 'asset_add_to_collection':
                $view = 'mail.notification.asset-added-collection';
                break;
            case 'collection_viewed':
                $view = 'mail.notification.collection-view-notification';
                break;
            case 'invitation_accept':
                $view = 'mail.notification.invitation-accepted';
                break;
            case 'sharelink_asset_download':
                $view = 'mail.notification.sharelink-asset-download';
                break;
            case 'share_link_expired':
                $view = 'mail.notification.expired-sharelink-notification';
                break;
            default:
                $view = 'mail.common-mail-notification';
                break;
            
        }
        if(!empty($this->asset)){
            $assetString = implode(',', $this->asset);
        }
        return (new MailMessage)
            ->subject($this->title)
            ->view($view, [
                'data' => [
                    'name'    => $notifiable->name ?? 'there',
                    'title'   => $this->title, 
                    'message' => $this->message,
                    'url'     => $this->url,
                    'loginUser'     => $this->loginUser,
                    'nmeOfObject'     => $this->nameOfObject,
                    'asset'     => $assetString??null,
                    'logo' => asset('images/degeestlogo.jpg')
                ],
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title'   => $this->title,
            'message' => $this->message,
            'url'     => $this->url,
            'type'    => $this->type,
        ];
    }
}
