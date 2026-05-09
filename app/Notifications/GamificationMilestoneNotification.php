<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GamificationMilestoneNotification extends Notification
{
    use Queueable;

    /**
     * @param  array{title: string, body: string, kind: string, icon_key?: ?string, url?: ?string}  $payload
     */
    public function __construct(
        public array $payload
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return array_merge(
            [
                'kind' => $this->payload['kind'] ?? 'gamification',
            ],
            $this->payload
        );
    }
}
