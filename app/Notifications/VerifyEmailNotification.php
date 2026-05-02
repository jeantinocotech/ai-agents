<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $verificationCodePlain,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = (int) Config::get('auth.verification.expire', 60);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes($minutes),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        return (new MailMessage)
            ->subject('Confirme o seu endereço de e-mail')
            ->line('Para activar a conta, clique no botão abaixo ou introduza o código de 6 dígitos na página de iniciar sessão (secção «Confirmar com código»).')
            ->line('O seu código é: **'.$this->verificationCodePlain.'**')
            ->action('Confirmar e-mail', $verificationUrl)
            ->line('O link e o código expiram em '.$minutes.' minutos.')
            ->line('Se não criou conta na plataforma, pode ignorar esta mensagem.');
    }
}
