<?php

namespace App\Services;

use App\Enums\InterviewApplicationOutcome;
use App\Models\AgentDocument;
use App\Models\GamificationBadgeDefinition;
use App\Models\GamificationRank;
use App\Models\GamificationScoreEvent;
use App\Models\GamificationScoreEventDefinition;
use App\Models\InterviewProcess;
use App\Models\MotivationLetter;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Models\UserCv;
use App\Models\UserGamificationSnapshot;
use App\Notifications\GamificationMilestoneNotification;
use Illuminate\Support\Facades\DB;

class GamificationService
{
    public function recordEvent(
        User $user,
        string $eventKey,
        ?string $referenceType = null,
        ?int $referenceId = null,
        array $meta = []
    ): void {
        GamificationScoreEvent::query()->create([
            'user_id' => (int) $user->id,
            'event_key' => $eventKey,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'meta' => $meta !== [] ? $meta : null,
            'occurred_at' => now(),
        ]);
    }

    public function refreshIfStale(User $user): UserGamificationSnapshot
    {
        return UserGamificationSnapshot::query()->where('user_id', $user->id)->first()
            ?? UserGamificationSnapshot::query()->create(['user_id' => (int) $user->id]);
    }

    public function ensureFreshSnapshot(User $user): UserGamificationSnapshot
    {
        return DB::transaction(function () use ($user) {
            $snapshot = UserGamificationSnapshot::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $snapshot) {
                $snapshot = UserGamificationSnapshot::query()->create(['user_id' => (int) $user->id]);
            }

            $definitionsHash = $this->definitionsHash();
            $lastEventAt = GamificationScoreEvent::query()
                ->where('user_id', $user->id)
                ->max('occurred_at');

            $isStale = ($snapshot->definitions_hash ?? '') !== $definitionsHash
                || ($lastEventAt !== null && ($snapshot->last_event_at === null || $snapshot->last_event_at->lt($lastEventAt)));

            if (! $isStale) {
                return $snapshot;
            }

            $hadPriorComputation = $snapshot->computed_at !== null;
            $previousRankId = $snapshot->rank_id;
            $previousBadgesState = is_array($snapshot->badges_state) ? $snapshot->badges_state : null;

            $scoreTotal = $this->computeScoreTotal($user);
            $rank = $this->resolveRank($scoreTotal);
            $badgesState = $this->computeBadgesState($user);

            $snapshot->score_total = $scoreTotal;
            $snapshot->rank_id = $rank?->id;
            $snapshot->badges_state = $badgesState;
            $snapshot->definitions_hash = $definitionsHash;
            $snapshot->computed_at = now();
            $snapshot->last_event_at = $lastEventAt ? now()->parse($lastEventAt) : $snapshot->last_event_at;
            $snapshot->save();

            if ($hadPriorComputation) {
                $this->notifyMilestonesIfProgressed(
                    $user,
                    $previousRankId,
                    $previousBadgesState,
                    $badgesState,
                    $rank?->id
                );
            }

            return $snapshot;
        });
    }

    public function definitionsHash(): string
    {
        $payload = [
            'badge_defs' => GamificationBadgeDefinition::query()
                ->with(['levels' => fn ($q) => $q->orderBy('threshold')])
                ->orderBy('key')
                ->get()
                ->map(fn ($d) => [
                    'key' => $d->key,
                    'label' => $d->label,
                    'counter_label' => $d->counter_label,
                    'is_active' => (bool) $d->is_active,
                    'levels' => $d->levels->map(fn ($l) => [
                        'threshold' => (int) $l->threshold,
                        'title' => (string) $l->title,
                        'icon_key' => $l->icon_key,
                        'color_key' => $l->color_key,
                    ])->all(),
                ])->all(),
            'event_defs' => GamificationScoreEventDefinition::query()
                ->orderBy('key')
                ->get(['key', 'points', 'daily_cap', 'is_active'])
                ->map(fn ($e) => [
                    'key' => $e->key,
                    'points' => (int) $e->points,
                    'daily_cap' => $e->daily_cap !== null ? (int) $e->daily_cap : null,
                    'is_active' => (bool) $e->is_active,
                ])->all(),
            'ranks' => GamificationRank::query()
                ->orderBy('min_points')
                ->get(['min_points', 'title', 'is_active'])
                ->map(fn ($r) => [
                    'min_points' => (int) $r->min_points,
                    'title' => (string) $r->title,
                    'is_active' => (bool) $r->is_active,
                ])->all(),
        ];

        return hash('sha256', json_encode($payload));
    }

    private function computeScoreTotal(User $user): int
    {
        $defs = GamificationScoreEventDefinition::query()
            ->where('is_active', true)
            ->get()
            ->keyBy('key');

        $events = GamificationScoreEvent::query()
            ->where('user_id', $user->id)
            ->orderBy('occurred_at')
            ->get();

        $dailyBuckets = []; // [event_key][Y-m-d] => points
        $sum = 0;

        foreach ($events as $event) {
            $key = (string) $event->event_key;

            // process_approved é stateful pelo estado atual do InterviewProcess; não soma pelo ledger.
            if ($key === 'process_approved') {
                continue;
            }

            $def = $defs->get($key);
            if (! $def || ! $def->is_active) {
                continue;
            }

            $points = (int) $def->points;
            if ($points === 0) {
                continue;
            }

            $cap = $def->daily_cap !== null ? (int) $def->daily_cap : null;
            if ($cap !== null && $cap > 0) {
                $day = $event->occurred_at?->format('Y-m-d') ?? (string) $event->created_at?->format('Y-m-d');
                $current = $dailyBuckets[$key][$day] ?? 0;
                if ($current >= $cap) {
                    continue;
                }
                $allowed = min($points, $cap - $current);
                $dailyBuckets[$key][$day] = $current + $allowed;
                $sum += $allowed;

                continue;
            }

            $sum += $points;
        }

        // Macro stateful: aprovações atuais contam, reversão remove automaticamente.
        $approvedDef = $defs->get('process_approved');
        if ($approvedDef && $approvedDef->is_active) {
            $approvedCount = InterviewProcess::query()
                ->where('user_id', $user->id)
                ->where('outcome', InterviewApplicationOutcome::Approved)
                ->count();
            $sum += max(0, $approvedCount) * (int) $approvedDef->points;
        }

        return max(0, $sum);
    }

    private function resolveRank(int $scoreTotal): ?GamificationRank
    {
        return GamificationRank::query()
            ->where('is_active', true)
            ->where('min_points', '<=', $scoreTotal)
            ->orderByDesc('min_points')
            ->first();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function computeBadgesState(User $user): array
    {
        $defs = GamificationBadgeDefinition::query()
            ->where('is_active', true)
            ->with(['levels' => fn ($q) => $q->orderBy('threshold')])
            ->orderBy('sort_order')
            ->get();

        $counters = $this->badgeCounters($user);
        $state = [];

        foreach ($defs as $def) {
            $count = (int) ($counters[$def->key] ?? 0);
            $levels = $def->levels;

            $current = null;
            $next = null;
            foreach ($levels as $level) {
                if ($count >= (int) $level->threshold) {
                    $current = $level;
                } elseif ($next === null) {
                    $next = $level;
                }
            }

            $state[$def->key] = [
                'key' => $def->key,
                'label' => $def->label,
                'counter_label' => $def->counter_label,
                'count' => $count,
                'current' => $current ? [
                    'threshold' => (int) $current->threshold,
                    'title' => (string) $current->title,
                    'icon_key' => $current->icon_key,
                    'color_key' => $current->color_key,
                ] : null,
                'next' => $next ? [
                    'threshold' => (int) $next->threshold,
                    'title' => (string) $next->title,
                    'icon_key' => $next->icon_key,
                    'color_key' => $next->color_key,
                ] : null,
            ];
        }

        return $state;
    }

    /**
     * @return array{cv:int,ats:int,motivation:int,interviews:int}
     */
    public function badgeCounters(User $user): array
    {
        $uid = (int) $user->id;

        $cvCount = UserCv::query()->where('user_id', $uid)->count();

        // ATS: conta JDs com user_cv_id (pareados) na biblioteca do agente ATS (quando configurado).
        $atsAgentId = CareerTrailStepCompletionAdapter::atsAgentIdForUser($user);
        $atsCount = 0;
        if ($atsAgentId !== null) {
            $atsCount = AgentDocument::query()
                ->where('user_id', $uid)
                ->where('agent_id', $atsAgentId)
                ->where('type', AgentDocument::TYPE_JD)
                ->whereNotNull('user_cv_id')
                ->count();
        }

        $motivationCount = MotivationLetter::query()->where('user_id', $uid)->count();

        // Entrevistas: rondas salvas (InterviewPreparation).
        $interviewsCount = DB::table('interview_preparations')->where('user_id', $uid)->count();

        return [
            'cv' => $cvCount,
            'ats' => $atsCount,
            'motivation' => $motivationCount,
            'interviews' => (int) $interviewsCount,
        ];
    }

    /**
     * Tokens usados (7d/30d) e por etapa (mapeamento simples).
     *
     * @return array{used_7d:int,used_30d:int,by_step:array<string,int>}
     */
    public function tokenUsageStats(User $user): array
    {
        $uid = (int) $user->id;
        $base = TokenTransaction::query()
            ->where('user_id', $uid)
            ->where('type', TokenTransaction::TYPE_USAGE);

        $used7d = (int) (clone $base)
            ->where('created_at', '>=', now()->subDays(7))
            ->sum(DB::raw('ABS(delta)'));
        $used30d = (int) (clone $base)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum(DB::raw('ABS(delta)'));

        $byStep = ['cv' => 0, 'ats' => 0, 'other' => 0];
        $rows = (clone $base)
            ->where('created_at', '>=', now()->subDays(30))
            ->get(['delta', 'meta']);

        foreach ($rows as $row) {
            $d = abs((int) $row->delta);
            $meta = is_array($row->meta) ? $row->meta : [];
            $billing = (string) ($meta['billing'] ?? '');

            if ($billing === 'chatkit_cv_assistant_turn') {
                $byStep['cv'] += $d;
            } elseif ($billing === 'chatkit_ats_consultation') {
                $byStep['ats'] += $d;
            } else {
                $byStep['other'] += $d;
            }
        }

        return [
            'used_7d' => $used7d,
            'used_30d' => $used30d,
            'by_step' => $byStep,
        ];
    }

    /**
     * Dispara apenas evoluções “positivas” (novo nível de badge ou rank com mais min_points).
     *
     * @param  array<string, array<string, mixed>>  $newBadgesState
     */
    private function notifyMilestonesIfProgressed(
        User $user,
        ?int $previousRankId,
        ?array $previousBadgesState,
        array $newBadgesState,
        ?int $newRankId,
    ): void {
        if ($previousRankId !== null && $newRankId !== null && $previousRankId !== $newRankId) {
            $oldRank = GamificationRank::query()->find($previousRankId);
            $newRank = GamificationRank::query()->find($newRankId);
            if ($oldRank && $newRank && (int) $newRank->min_points > (int) $oldRank->min_points) {
                $user->notify(new GamificationMilestoneNotification([
                    'kind' => 'rank_up',
                    'title' => 'Novo rank!',
                    'body' => 'Atingiu o rank '.$newRank->title.'.',
                    'icon_key' => $newRank->icon_key,
                    'url' => route('dashboard'),
                ]));
            }
        }

        if (! is_array($previousBadgesState)) {
            return;
        }

        foreach ($newBadgesState as $key => $state) {
            if (! is_array($state)) {
                continue;
            }
            $prev = $previousBadgesState[$key] ?? [];
            $oldTh = isset($prev['current']['threshold']) ? (int) $prev['current']['threshold'] : null;
            $newTh = isset($state['current']['threshold']) ? (int) $state['current']['threshold'] : null;

            if ($newTh === null || ($oldTh !== null && $newTh <= $oldTh)) {
                continue;
            }

            $label = is_string($state['label'] ?? null) ? (string) $state['label'] : (string) $key;
            $title = is_string(($state['current']['title'] ?? null)) ? (string) $state['current']['title'] : '';
            $icon = isset($state['current']['icon_key']) && ($state['current']['icon_key'] !== '' && $state['current']['icon_key'] !== null)
                ? (string) $state['current']['icon_key']
                : null;

            $user->notify(new GamificationMilestoneNotification([
                'kind' => 'badge_level_up',
                'title' => 'Progresso na trilha',
                'body' => $title !== '' ? "{$label}: {$title}" : $label.'.',
                'icon_key' => $icon,
                'url' => route('dashboard'),
            ]));
        }
    }
}
