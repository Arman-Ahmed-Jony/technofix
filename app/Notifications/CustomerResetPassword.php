<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

/** Password reset email for storefront customers (links to the storefront, not the admin). */
class CustomerResetPassword extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        return route('account.password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
    }
}
