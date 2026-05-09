<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GamificationBadgeDefinition;
use App\Models\GamificationBadgeLevel;
use App\Models\GamificationRank;
use App\Models\GamificationScoreEventDefinition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GamificationController extends Controller
{
    public function index(Request $request): View
    {
        $tab = (string) ($request->query('tab') ?? 'badges');
        if (! in_array($tab, ['badges', 'score'], true)) {
            $tab = 'badges';
        }

        $badgeDefs = GamificationBadgeDefinition::query()
            ->with('levels')
            ->orderBy('sort_order')
            ->get();

        $eventDefs = GamificationScoreEventDefinition::query()
            ->orderBy('key')
            ->get();

        $ranks = GamificationRank::query()
            ->orderBy('min_points')
            ->get();

        return view('admin.gamification.index', compact('tab', 'badgeDefs', 'eventDefs', 'ranks'));
    }

    public function updateBadges(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'definitions' => ['required', 'array'],
            'definitions.*.id' => ['required', 'integer', 'exists:gamification_badge_definitions,id'],
            'definitions.*.label' => ['required', 'string', 'max:64'],
            'definitions.*.counter_label' => ['nullable', 'string', 'max:96'],
            'definitions.*.sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'definitions.*.is_active' => ['nullable', 'boolean'],
            'levels' => ['required', 'array'],
            'levels.*.id' => ['required', 'integer', 'exists:gamification_badge_levels,id'],
            'levels.*.badge_definition_id' => ['required', 'integer', 'exists:gamification_badge_definitions,id'],
            'levels.*.threshold' => ['required', 'integer', 'min:1', 'max:1000000'],
            'levels.*.title' => ['required', 'string', 'max:64'],
            'levels.*.icon_key' => ['nullable', 'string', 'max:64'],
        ]);

        $levelsByDef = [];
        foreach ($validated['levels'] as $row) {
            $levelsByDef[(int) $row['badge_definition_id']][] = (int) $row['threshold'];
        }
        foreach ($levelsByDef as $defId => $thresholds) {
            $sorted = $thresholds;
            sort($sorted);
            if ($sorted !== $thresholds) {
                // Não exigimos ordem no form, mas exigimos valores únicos.
                $unique = array_values(array_unique($sorted));
                if (count($unique) !== count($sorted)) {
                    throw ValidationException::withMessages([
                        'levels' => 'Thresholds duplicados num badge não são permitidos.',
                    ]);
                }
            } else {
                $unique = array_values(array_unique($thresholds));
                if (count($unique) !== count($thresholds)) {
                    throw ValidationException::withMessages([
                        'levels' => 'Thresholds duplicados num badge não são permitidos.',
                    ]);
                }
            }
        }

        foreach ($validated['definitions'] as $row) {
            GamificationBadgeDefinition::query()
                ->whereKey((int) $row['id'])
                ->update([
                    'label' => (string) $row['label'],
                    'counter_label' => $row['counter_label'] !== null ? (string) $row['counter_label'] : null,
                    'sort_order' => (int) $row['sort_order'],
                    'is_active' => (bool) ($row['is_active'] ?? false),
                ]);
        }

        foreach ($validated['levels'] as $row) {
            GamificationBadgeLevel::query()
                ->whereKey((int) $row['id'])
                ->update([
                    'threshold' => (int) $row['threshold'],
                    'title' => (string) $row['title'],
                    'icon_key' => $row['icon_key'] !== null ? (string) $row['icon_key'] : null,
                ]);
        }

        return redirect()
            ->route('admin.gamification.index', ['tab' => 'badges'])
            ->with('success', 'Badges atualizados.');
    }

    public function updateScore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'events' => ['required', 'array'],
            'events.*.id' => ['required', 'integer', 'exists:gamification_score_event_definitions,id'],
            'events.*.label' => ['required', 'string', 'max:96'],
            'events.*.points' => ['required', 'integer', 'min:-100000', 'max:100000'],
            'events.*.daily_cap' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'events.*.is_active' => ['nullable', 'boolean'],
            'ranks' => ['required', 'array'],
            'ranks.*.id' => ['required', 'integer', 'exists:gamification_ranks,id'],
            'ranks.*.min_points' => ['required', 'integer', 'min:0', 'max:100000000'],
            'ranks.*.title' => ['required', 'string', 'max:64'],
            'ranks.*.icon_key' => ['nullable', 'string', 'max:64'],
            'ranks.*.is_active' => ['nullable', 'boolean'],
        ]);

        $rankPoints = array_map(fn ($r) => (int) $r['min_points'], $validated['ranks']);
        $unique = array_values(array_unique($rankPoints));
        if (count($unique) !== count($rankPoints)) {
            throw ValidationException::withMessages([
                'ranks' => 'min_points duplicados em ranks não são permitidos.',
            ]);
        }

        foreach ($validated['events'] as $row) {
            GamificationScoreEventDefinition::query()
                ->whereKey((int) $row['id'])
                ->update([
                    'label' => (string) $row['label'],
                    'points' => (int) $row['points'],
                    'daily_cap' => $row['daily_cap'] !== null ? (int) $row['daily_cap'] : null,
                    'is_active' => (bool) ($row['is_active'] ?? false),
                ]);
        }

        foreach ($validated['ranks'] as $row) {
            GamificationRank::query()
                ->whereKey((int) $row['id'])
                ->update([
                    'min_points' => (int) $row['min_points'],
                    'title' => (string) $row['title'],
                    'icon_key' => filled($row['icon_key'] ?? null) ? (string) $row['icon_key'] : null,
                    'is_active' => (bool) ($row['is_active'] ?? false),
                ]);
        }

        return redirect()
            ->route('admin.gamification.index', ['tab' => 'score'])
            ->with('success', 'Score e ranks atualizados.');
    }
}
