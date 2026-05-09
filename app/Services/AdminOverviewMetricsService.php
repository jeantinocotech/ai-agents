<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\GamificationScoreEvent;
use App\Models\GamificationScoreEventDefinition;
use App\Models\TokenPackOrder;
use App\Models\TokenTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminOverviewMetricsService
{
    public const PERIOD_WEEK = '7d';

    public const PERIOD_30D = '30d';

    public const PERIOD_MONTHS = '12m';

    /** @var array<string, string> Labels em PT para a UI */
    public const PERIOD_LABELS = [
        self::PERIOD_WEEK => 'Últimos 7 dias',
        self::PERIOD_30D => 'Últimos 30 dias',
        self::PERIOD_MONTHS => 'Últimos 12 meses',
    ];

    /**
     * @return array{
     *   key: string,
     *   label: string,
     *   starts_at: \Carbon\Carbon,
     *   ends_at: \Carbon\Carbon,
     *   bucket: 'day'|'month',
     *   bucket_keys: list<string>,
     *   bucket_labels: list<string>,
     * }
     */
    public function resolvePeriod(?string $input): array
    {
        $key = match ($input) {
            self::PERIOD_30D => self::PERIOD_30D,
            self::PERIOD_MONTHS => self::PERIOD_MONTHS,
            default => self::PERIOD_WEEK,
        };

        $endsAt = Carbon::now()->endOfDay();

        if ($key === self::PERIOD_MONTHS) {
            $startsAt = Carbon::now()->copy()->startOfMonth()->subMonths(11)->startOfDay();

            return array_merge([
                'key' => $key,
                'label' => self::PERIOD_LABELS[$key],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'bucket' => 'month',
            ], $this->buildMonthBuckets($startsAt, $endsAt));

        }

        $days = $key === self::PERIOD_30D ? 29 : 6;

        $startsAt = Carbon::now()->copy()->startOfDay()->subDays($days);

        return array_merge([
            'key' => $key,
            'label' => self::PERIOD_LABELS[$key],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'bucket' => 'day',
        ], $this->buildDayBuckets($startsAt, $endsAt));

    }

    /**
     * @return array{kpis: array<string, mixed>, charts: array<string, mixed>, gamification_breakdown: \Illuminate\Support\Collection, recent_gamification_events: \Illuminate\Support\Collection}
     */
    public function gather(array $period): array
    {
        $start = $period['starts_at'];
        $end = $period['ends_at'];
        $keys = $period['bucket_keys'];

        return [
            'kpis' => $this->computeKpis($start, $end),
            'charts' => [
                'activity' => [
                    'labels' => $period['bucket_labels'],
                    'sessions' => $this->seriesFor(ChatSession::class, 'created_at', $keys, $start, $end, $period['bucket']),
                    'new_users' => $this->seriesFor(User::class, 'created_at', $keys, $start, $end, $period['bucket']),
                ],
                'tokens' => [
                    'labels' => $period['bucket_labels'],
                    'consumption' => $this->tokenConsumptionSeries($keys, $start, $end, $period['bucket']),
                    'incoming' => $this->tokenIncomingSeries($keys, $start, $end, $period['bucket']),
                ],
                'gamification' => [
                    'labels' => $period['bucket_labels'],
                    'events' => $this->seriesFor(GamificationScoreEvent::class, 'occurred_at', $keys, $start, $end, $period['bucket']),
                ],
            ],
            'gamification_breakdown' => $this->gamificationEventBreakdown($start, $end),
            'recent_gamification_events' => GamificationScoreEvent::query()
                ->with(['user:id,name,email'])
                ->whereBetween('occurred_at', [$start, $end])
                ->orderByDesc('occurred_at')
                ->limit(25)
                ->get(['id', 'user_id', 'event_key', 'occurred_at', 'meta']),
        ];

    }

    /**
     * @return array<string, mixed>
     */
    private function computeKpis(Carbon $start, Carbon $end): array
    {
        $newUsers = User::query()->whereBetween('created_at', [$start, $end])->count();

        $sessions = ChatSession::query()->whereBetween('created_at', [$start, $end])->count();

        $tokensConsumed = (int) TokenTransaction::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('delta', '<', 0)
            ->sum(DB::raw('ABS(delta)'));

        $tokensIncoming = (int) TokenTransaction::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('delta', '>', 0)
            ->sum('delta');

        $ordersCompleted = TokenPackOrder::query()
            ->where('status', TokenPackOrder::STATUS_COMPLETED)
            ->whereBetween('updated_at', [$start, $end])
            ->get(['tokens_amount', 'amount_brl']);

        $purchaseCount = $ordersCompleted->count();
        $purchaseTokens = (int) $ordersCompleted->sum('tokens_amount');
        $purchaseBrl = (float) $ordersCompleted->sum('amount_brl');

        $gamificationEvents = GamificationScoreEvent::query()->whereBetween('occurred_at', [$start, $end])->count();

        $gamificationPoints = (int) round((float) GamificationScoreEvent::query()
            ->join(
                'gamification_score_event_definitions as d',
                'd.key',
                '=',
                'gamification_score_events.event_key'
            )
            ->where('d.is_active', true)
            ->whereBetween('gamification_score_events.occurred_at', [$start, $end])
            ->sum('d.points'));

        $distinctActiveUsers = $this->countDistinctUsersWithActivity($start, $end);

        return [
            'new_users' => $newUsers,
            'chat_sessions' => $sessions,
            'tokens_consumed_total' => $tokensConsumed,
            'tokens_credit_total' => $tokensIncoming,
            'purchases_count' => $purchaseCount,
            'purchases_tokens_volume' => $purchaseTokens,
            'purchases_revenue_brl' => $purchaseBrl,
            'gamification_event_count' => $gamificationEvents,
            'gamification_points_estimate' => $gamificationPoints,
            'distinct_active_users' => $distinctActiveUsers,
            'registered_users_total' => User::query()->count(),
        ];

    }

    private function countDistinctUsersWithActivity(Carbon $start, Carbon $end): int
    {
        $q = '('
            .' SELECT user_id FROM chat_sessions WHERE created_at BETWEEN ? AND ? '
            .' UNION '
            .' SELECT user_id FROM token_transactions WHERE created_at BETWEEN ? AND ? '
            .' UNION '
            .' SELECT user_id FROM gamification_score_events WHERE occurred_at BETWEEN ? AND ? '
            .') AS u';

        $bindings = [
            $start, $end,
            $start, $end,
            $start, $end,
        ];

        $row = DB::selectOne(
            'SELECT COUNT(DISTINCT user_id) AS c FROM '.$q,
            $bindings,
        );

        return (int) ($row->c ?? 0);
    }

    /**
     * Agregações por período usando expressão compatível SQLite / MySQL.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     * @param  list<string>  $bucketKeys
     * @return list<int>
     */
    private function seriesFor(
        string $modelClass,
        string $column,
        array $bucketKeys,
        Carbon $start,
        Carbon $end,
        string $granularity,
    ): array {

        $expr = $this->bucketSelectExpression($column, $granularity);

        /** @phpstan-ignore-next-line dynamic static */
        $q = $modelClass::query()
            ->whereBetween($column, [$start, $end])
            ->selectRaw($expr.' as bk, COUNT(*) as c')
            ->groupByRaw($expr);

        $map = [];

        foreach ($q->get() as $row) {

            /** @phpstan-ignore-next-line */
            $map[(string) $row->bk] = (int) $row->c;

        }

        $out = [];

        foreach ($bucketKeys as $k) {
            $out[] = (int) ($map[$k] ?? 0);
        }

        return $out;

    }

    /**
     * @param  list<string>  $bucketKeys
     * @return list<int>
     */
    private function tokenConsumptionSeries(
        array $bucketKeys,
        Carbon $start,
        Carbon $end,
        string $granularity,
    ): array {
        $expr = $this->bucketSelectExpression('created_at', $granularity);
        $q = TokenTransaction::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('delta', '<', 0)
            ->selectRaw($expr.' as bk, SUM(ABS(delta)) as s')
            ->groupByRaw($expr);

        $map = [];

        foreach ($q->get() as $row) {

            /** @phpstan-ignore-next-line */
            $map[(string) $row->bk] = (int) $row->s;

        }

        $out = [];

        foreach ($bucketKeys as $k) {
            $out[] = (int) ($map[$k] ?? 0);
        }

        return $out;

    }

    /**
     * @param  list<string>  $bucketKeys
     * @return list<int>
     */
    private function tokenIncomingSeries(
        array $bucketKeys,
        Carbon $start,
        Carbon $end,
        string $granularity,
    ): array {
        $expr = $this->bucketSelectExpression('created_at', $granularity);
        $q = TokenTransaction::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('delta', '>', 0)
            ->selectRaw($expr.' as bk, SUM(delta) as s')
            ->groupByRaw($expr);

        $map = [];

        foreach ($q->get() as $row) {

            /** @phpstan-ignore-next-line */
            $map[(string) $row->bk] = (int) $row->s;

        }

        $out = [];

        foreach ($bucketKeys as $k) {
            $out[] = (int) ($map[$k] ?? 0);
        }

        return $out;

    }

    private function bucketSelectExpression(string $column, string $granularity): string
    {
        if ($granularity === 'month') {
            return match (DB::connection()->getDriverName()) {
                'sqlite' => "strftime('%Y-%m', {$column})",
                default => "DATE_FORMAT({$column}, '%Y-%m')",
            };
        }

        return match (DB::connection()->getDriverName()) {
            'sqlite' => "date({$column})",
            default => "DATE({$column})",
        };

    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function gamificationEventBreakdown(Carbon $start, Carbon $end)
    {

        $rows = GamificationScoreEvent::query()
            ->whereBetween('occurred_at', [$start, $end])
            ->selectRaw('event_key, COUNT(*) as event_count')
            ->groupBy('event_key')
            ->orderByDesc('event_count')
            ->limit(20)
            ->get();

        $defs = GamificationScoreEventDefinition::query()
            ->get(['key', 'label', 'points'])
            ->keyBy('key');

        return $rows->map(function ($row) use ($defs) {
            $def = $defs->get($row->event_key);

            return (object) [
                'event_key' => $row->event_key,
                'label' => $def?->label ?? $row->event_key,
                'points_per_event' => $def?->points,
                'event_count' => (int) $row->event_count,
            ];

        });

    }

    /**
     * @return array{bucket_keys: list<string>, bucket_labels: list<string>}
     */
    private function buildDayBuckets(Carbon $startsAt, Carbon $endsAt): array
    {
        $keys = [];
        $labels = [];
        $cursor = $startsAt->copy()->startOfDay();
        $last = $endsAt->copy()->startOfDay();

        while ($cursor->lte($last)) {
            $keys[] = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('d/m');
            $cursor->addDay();
        }

        return [
            'bucket_keys' => $keys,
            'bucket_labels' => $labels,
        ];

    }

    /**
     * @return array{bucket_keys: list<string>, bucket_labels: list<string>}
     */
    private function buildMonthBuckets(Carbon $startsAt, Carbon $endsAt): array
    {
        $keys = [];
        $labels = [];
        $cursor = $startsAt->copy()->startOfMonth();
        $last = $endsAt->copy()->startOfMonth();

        while ($cursor->lte($last)) {
            $keys[] = $cursor->format('Y-m');
            $labels[] = $cursor->format('m/Y');
            $cursor->addMonth();
        }

        return [
            'bucket_keys' => $keys,
            'bucket_labels' => $labels,
        ];

    }
}
