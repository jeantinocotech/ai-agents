<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserCv;

class UserCvDuplicateService
{
    public function titleForJobCopy(string $cvTitle, ?string $jobTitle): string
    {
        $base = trim($cvTitle) !== '' ? trim($cvTitle) : 'CV';
        $job = trim((string) $jobTitle);

        $title = $job !== '' ? $base.' — '.$job : 'Cópia de '.$base;

        return $this->limitTitle($title);
    }

    public function titleForGenericCopy(string $cvTitle): string
    {
        $base = trim($cvTitle) !== '' ? trim($cvTitle) : 'CV';

        return $this->limitTitle('Cópia de '.$base);
    }

    public function duplicate(User $user, UserCv $source, string $newTitle): UserCv
    {
        abort_unless((int) $source->user_id === (int) $user->id, 403);

        return UserCv::query()->create([
            'user_id' => $user->id,
            'title' => $this->limitTitle($newTitle),
            'body' => $source->body,
            'is_default' => false,
            'source' => UserCv::SOURCE_MANUAL,
        ]);
    }

    private function limitTitle(string $title): string
    {
        if (mb_strlen($title) <= 255) {
            return $title;
        }

        return mb_substr($title, 0, 252).'...';
    }
}
