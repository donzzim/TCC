<?php

namespace App\Filament\Egap\Auth;

use Filament\Auth\Pages\PasswordReset\ResetPassword;

class ResetPasswordEgap extends ResetPassword
{
    /**
     * @var view-string
     */
    protected string $view = 'egap.auth.reset-password';
}
