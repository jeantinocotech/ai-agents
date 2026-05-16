<?php

namespace App\Services;

use App\Mail\ChatKitClientAlertMail;
use App\Models\Agent;
use App\Models\User;
use App\Support\LogAlertMailRecipients;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class ChatKitClientAlertNotifier
{
    public function notifyIfWarranted(
        User $user,
        Agent $agent,
        string $message,
        ?string $source,
        string $referer = '',
    ): void {
        if (! $this->isEnabled()) {
            return;
        }

        $recipients = LogAlertMailRecipients::parse();
        if ($recipients === []) {
            return;
        }

        $source = $source !== null && $source !== '' ? $source : 'unknown';
        if (! $this->shouldNotify($source, $message)) {
            return;
        }

        $throttleKey = $this->throttleCacheKey((int) $user->id, $source, $message);
        $throttleSeconds = max(60, (int) config('services.chatkit.alert_throttle_seconds', 21600));

        if (! Cache::add($throttleKey, 1, $throttleSeconds)) {
            return;
        }

        try {
            Mail::to($recipients)->send(new ChatKitClientAlertMail(
                userId: (int) $user->id,
                userEmail: (string) $user->email,
                agentId: (int) $agent->id,
                agentName: (string) $agent->name,
                source: $source,
                message: $message,
                referer: $referer,
                reportedAt: now(),
            ));
        } catch (Throwable $e) {
            Cache::forget($throttleKey);
            Log::error('ChatKit alert email failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'agent_id' => $agent->id,
                'source' => $source,
            ]);
        }
    }

    private function isEnabled(): bool
    {
        if ((bool) config('services.chatkit.alert_only_production', true)) {
            return app()->environment('production');
        }

        return true;
    }

    private function shouldNotify(string $source, string $message): bool
    {
        $message = trim($message);
        if ($message === '') {
            return false;
        }

        if ($source === 'chatkit.error') {
            return true;
        }

        $lower = mb_strtolower($message);

        if (in_array($source, ['chatkit_send_cv', 'chatkit_send_jd'], true)) {
            return $this->messageMatchesAlertPatterns($lower);
        }

        if ($source === 'chatkit_session') {
            if (preg_match('/session_http_40[12]/', $lower)) {
                return false;
            }
            if (preg_match('/session_http_419/', $lower)) {
                return false;
            }
            if (preg_match('/session_http_5\d\d/', $lower)) {
                return true;
            }

            return $this->messageMatchesAlertPatterns($lower);
        }

        return false;
    }

    private function messageMatchesAlertPatterns(string $lowerMessage): bool
    {
        $needles = [
            'not supported',
            'sendusermessage',
            'domain verification',
            'openai recusou',
            'não foi possível iniciar o chatkit',
            'nao foi possivel iniciar o chatkit',
            'erro no servidor ao criar sessão',
            'erro no servidor ao criar sessao',
        ];

        foreach ($needles as $needle) {
            if (str_contains($lowerMessage, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function throttleCacheKey(int $userId, string $source, string $message): string
    {
        $normalized = mb_strtolower(trim($message));
        if (mb_strlen($normalized) > 300) {
            $normalized = mb_substr($normalized, 0, 300);
        }

        $digest = hash('sha256', $userId.'|'.$source.'|'.$normalized);

        return 'chatkit_client_alert:'.$digest;
    }
}
