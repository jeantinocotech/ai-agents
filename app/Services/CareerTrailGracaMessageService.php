<?php

namespace App\Services;

use App\Models\CareerTrailGracaMessage;
use Illuminate\Support\Collection;

final class CareerTrailGracaMessageService
{
    /**
     * Corpos activos, por ordem, para um passo (ou sem passo) e slot.
     *
     * @return Collection<int, string>
     */
    public static function bodies(string $processKey, ?int $careerTrailStepId, string $slot): Collection
    {
        return CareerTrailGracaMessage::query()
            ->where('process_key', $processKey)
            ->where('slot', $slot)
            ->where('is_active', true)
            ->when(
                $careerTrailStepId === null,
                fn ($q) => $q->whereNull('career_trail_step_id'),
                fn ($q) => $q->where('career_trail_step_id', $careerTrailStepId)
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('body')
            ->map(fn ($b) => is_string($b) ? trim($b) : '')
            ->filter(fn (string $b) => $b !== '');
    }
}
