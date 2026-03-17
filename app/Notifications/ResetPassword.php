<?php
namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPassword extends BaseResetPassword
{
    use Queueable;
    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $email    = $notifiable->getEmailForPasswordReset();
        $resetUrl = config('deegest.reset_url') . '?token=' . $this->token . '&email=' . urlencode($email);

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->view('mail.password-reset', [
                'resetUrl' => $resetUrl,
                'user'     => $notifiable,
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
            //
        ];
    }
}
