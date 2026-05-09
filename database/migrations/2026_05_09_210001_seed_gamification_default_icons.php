<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Preenche icon_key apenas onde ainda está vazio (preserva edições no admin).
     * Formato atual do UI: texto exibido tal qual — usar emojis (ex.: 📄).
     */
    public function up(): void
    {
        $byBadgeThreshold = [
            'cv' => [1 => '📄', 3 => '📝', 10 => '📑', 20 => '📚', 30 => '🏛️'],
            'ats' => [1 => '🎯', 3 => '🔗', 10 => '🧭', 20 => '🧩', 30 => '🎼'],
            'motivation' => [1 => '✉️', 3 => '💬', 10 => '📣', 20 => '🎙️', 30 => '✨'],
            'interviews' => [1 => '🎬', 3 => '🏋️', 10 => '🎭', 20 => '🔥', 30 => '👑'],
        ];

        $levels = DB::table('gamification_badge_levels as l')
            ->join('gamification_badge_definitions as d', 'd.id', '=', 'l.badge_definition_id')
            ->whereNull('l.icon_key')
            ->select(['l.id', 'd.key as badge_key', 'l.threshold'])
            ->get();

        foreach ($levels as $row) {
            $emoji = $byBadgeThreshold[$row->badge_key][$row->threshold] ?? null;
            if ($emoji !== null) {
                DB::table('gamification_badge_levels')
                    ->where('id', $row->id)
                    ->update(['icon_key' => $emoji]);
            }
        }

        $byMinPoints = [
            0 => '👣',
            100 => '🔭',
            300 => '🌱',
            600 => '🛠️',
            900 => '🧭',
            1200 => '⭐',
            1700 => '🎖️',
            2300 => '🚀',
            3000 => '🏆',
        ];

        $ranks = DB::table('gamification_ranks')
            ->whereNull('icon_key')
            ->select(['id', 'min_points'])
            ->get();

        foreach ($ranks as $rank) {
            $emoji = $byMinPoints[(int) $rank->min_points] ?? null;
            if ($emoji !== null) {
                DB::table('gamification_ranks')
                    ->where('id', $rank->id)
                    ->update(['icon_key' => $emoji]);
            }
        }
    }

    public function down(): void
    {
        // Sem reversão segura — ícones podem ter sido editados manualmente.
    }
};
