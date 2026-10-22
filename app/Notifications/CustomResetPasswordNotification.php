<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class CustomResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $token,
        public string $resetUrl,

    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        Log::info('CUSTOM RESET MAIL DEBUG', [
            'notifiable_id' => $notifiable->id ?? null,
            'to' => $notifiable->email ?? null,
            'mailer' => 'smtp', // troque para o mailer que já funciona na tua app
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'queue_default' => config('queue.default'),
            'reset_url' => $this->resetUrl,
            'token' => $this->token,
        ]);





        return (new MailMessage)
            ->mailer('smtp') // <-- troque aqui pro mailer bom
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Recuperação de senha - Educa EMES')
            ->greeting('Olá!')
            ->line('Recebemos uma solicitação para redefinir a sua senha.')
            ->action('Redefinir senha', $this->resetUrl)
            ->line('Este link expirará em 60 minutos.')
            ->line('Se você não solicitou a redefinição, ignore este e-mail.');
    }
}
