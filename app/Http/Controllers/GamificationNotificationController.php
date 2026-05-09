<?php

namespace App\Http\Controllers;

use App\Notifications\GamificationMilestoneNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GamificationNotificationController extends Controller
{
    public function recent(Request $request): JsonResponse
    {
        $rows = $request->user()
            ->notifications()
            ->where('type', GamificationMilestoneNotification::class)
            ->latest()
            ->limit(25)
            ->get(['id', 'data', 'read_at', 'created_at']);

        return response()->json([
            'items' => $rows->map(fn ($n) => [
                'id' => $n->id,
                'read' => $n->read_at !== null,
                'data' => $n->data,
                'created_at' => $n->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function unread(Request $request): JsonResponse
    {
        $rows = $request->user()
            ->unreadNotifications()
            ->where('type', GamificationMilestoneNotification::class)
            ->latest()
            ->limit(25)
            ->get(['id', 'data', 'created_at']);

        return response()->json([
            'count' => $request->user()
                ->unreadNotifications()
                ->where('type', GamificationMilestoneNotification::class)
                ->count(),
            'items' => $rows->map(fn ($n) => [
                'id' => $n->id,
                'data' => $n->data,
                'created_at' => $n->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function markRead(Request $request, string $notification): Response
    {
        $updated = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->where('type', GamificationMilestoneNotification::class)
            ->update(['read_at' => now()]);

        abort_if($updated === 0, 404);

        return response()->noContent();
    }

    public function markAllRead(Request $request): Response
    {
        $request->user()
            ->unreadNotifications()
            ->where('type', GamificationMilestoneNotification::class)
            ->update(['read_at' => now()]);

        return response()->noContent();
    }
}
