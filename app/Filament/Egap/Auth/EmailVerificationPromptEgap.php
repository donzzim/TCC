<?php

namespace App\Filament\Egap\Auth;

use Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt;

class EmailVerificationPromptEgap extends EmailVerificationPrompt
{
    /**
     * @var view-string
     */
    protected string $view = 'egap.auth.email-verification-prompt';
}
