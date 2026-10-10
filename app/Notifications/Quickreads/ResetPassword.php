<?php

namespace App\Notifications\Quickreads;

use Illuminate\Auth\Notifications\ResetPassword as Notification;

class ResetPassword extends Notification
{
    protected function resetUrl($notifiable)
    {
        return config('quickreads_v2.password_reset_url').'?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], '', '&', PHP_QUERY_RFC3986);
    }
}
