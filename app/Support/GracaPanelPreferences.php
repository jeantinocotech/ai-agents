<?php

namespace App\Support;

use App\Models\User;

/**
 * Preferências do painel «Orientação — Graça» (expandir/ocultar) na área autenticada.
 */
final class GracaPanelPreferences
{
    public const PAGE_TRAIL_MAP = 'trail_map';

    public const PAGE_TRAIL_ATS = 'trail_ats';

    public const PAGE_TRAIL_CV = 'trail_cv';

    public const PAGE_CHAT_ATS = 'chat_ats';

    public const PAGE_CHAT_COVER_LETTER = 'chat_cover_letter';

    public const PAGE_CHAT_INTERVIEWS = 'chat_interviews';

    public const PAGE_CHAT_CV = 'chat_cv';

    /** @return list<string> */
    public static function pageKeys(): array
    {
        return [
            self::PAGE_TRAIL_MAP,
            self::PAGE_TRAIL_ATS,
            self::PAGE_TRAIL_CV,
            self::PAGE_CHAT_ATS,
            self::PAGE_CHAT_COVER_LETTER,
            self::PAGE_CHAT_INTERVIEWS,
            self::PAGE_CHAT_CV,
        ];
    }

    public static function chatPageKeyForStepSlug(?string $slug): string
    {
        return match ($slug) {
            'ats' => self::PAGE_CHAT_ATS,
            'cover-letter' => self::PAGE_CHAT_COVER_LETTER,
            'interviews' => self::PAGE_CHAT_INTERVIEWS,
            'cv' => self::PAGE_CHAT_CV,
            default => self::PAGE_CHAT_ATS,
        };
    }

    /**
     * @return array{global_collapsed: bool, pages: array<string, bool>, dismissed_global_prompt: bool}
     */
    public static function read(User $user): array
    {
        $stored = $user->ui_preferences['graca_panel'] ?? [];

        $pages = [];
        foreach ((array) ($stored['pages'] ?? []) as $key => $value) {
            if (is_string($key) && in_array($key, self::pageKeys(), true)) {
                $pages[$key] = (bool) $value;
            }
        }

        return [
            'global_collapsed' => (bool) ($stored['global_collapsed'] ?? false),
            'pages' => $pages,
            'dismissed_global_prompt' => (bool) ($stored['dismissed_global_prompt'] ?? false),
        ];
    }

    public static function isCollapsed(User $user, string $pageKey): bool
    {
        abort_unless(in_array($pageKey, self::pageKeys(), true), 500, 'Chave de página Graça inválida.');

        $prefs = self::read($user);
        $pages = $prefs['pages'];

        if (array_key_exists($pageKey, $pages)) {
            return $pages[$pageKey];
        }

        return $prefs['global_collapsed'];
    }

    /**
     * @param  array{page_key: string, collapsed: bool, apply_global?: bool|null, dismiss_global_prompt?: bool}  $data
     */
    public static function applyUpdate(User $user, array $data): void
    {
        $pageKey = $data['page_key'];
        abort_unless(in_array($pageKey, self::pageKeys(), true), 422, 'Chave de página inválida.');

        $prefs = self::read($user);

        if (! empty($data['dismiss_global_prompt'])) {
            $prefs['dismissed_global_prompt'] = true;
        }

        if (array_key_exists('apply_global', $data) && $data['apply_global'] === true) {
            $prefs['global_collapsed'] = true;
            $prefs['dismissed_global_prompt'] = true;
        } elseif (array_key_exists('apply_global', $data) && $data['apply_global'] === false) {
            $prefs['dismissed_global_prompt'] = true;
        }

        $prefs['pages'][$pageKey] = (bool) $data['collapsed'];

        $ui = $user->ui_preferences ?? [];
        $ui['graca_panel'] = $prefs;
        $user->ui_preferences = $ui;
        $user->save();
    }
}
