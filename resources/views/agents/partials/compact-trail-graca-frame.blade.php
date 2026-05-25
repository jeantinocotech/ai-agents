{{-- Caixa da Graça no topo do chat compacto da trilha — usa painel colapsável unificado. --}}
@props([
    'gracaStep',
    'gracaSlot',
])

@php
    use App\Services\CareerTrailGracaMessageService;
    use App\Support\GracaPanelPreferences;

    $bodies = CareerTrailGracaMessageService::bodies(
        'career_trail',
        (int) $gracaStep->getKey(),
        $gracaSlot
    );

    $fallback = match ($gracaStep->slug) {
        'ats' => trim((string) config('career_trail.ats_chat_graca_fallback')),
        'cover-letter' => trim((string) config('career_trail.cover_letter_chat_graca_fallback')),
        'interviews' => trim((string) config('career_trail.interviews_chat_graca_fallback')),
        'cv' => trim((string) config('career_trail.cv_assistant_chat_graca_fallback')),
        default => '',
    };

    $summaryLine = match ($gracaStep->slug) {
        'ats' => (string) config('career_trail.ats_chat_graca_summary_line', ''),
        'cover-letter' => (string) config('career_trail.cover_letter_chat_graca_summary_line', ''),
        'interviews' => (string) config('career_trail.interviews_chat_graca_summary_line', ''),
        'cv' => (string) config('career_trail.cv_assistant_chat_graca_summary_line', ''),
        default => '',
    };

    $paragraphs = $bodies->isNotEmpty()
        ? $bodies->map(fn ($b) => trim((string) $b))->filter()
        : collect($fallback !== '' ? [$fallback] : []);

    $pageKey = GracaPanelPreferences::chatPageKeyForStepSlug($gracaStep->slug ?? null);
@endphp

<x-graca-orientation-panel
    :page-key="$pageKey"
    :subtitle="$summaryLine !== '' ? $summaryLine : null"
    border-class="border-slate-200/90 bg-white ring-1 ring-slate-100"
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
        <div class="shrink-0 rounded-2xl bg-white p-1 shadow ring-1 ring-violet-100">
            <x-graca-avatar size="md" />
        </div>
        @if ($paragraphs->isNotEmpty())
            <div class="min-w-0 flex-1 space-y-2 text-sm leading-relaxed text-slate-700">
                @foreach ($paragraphs as $para)
                    <p>{!! nl2br(e($para)) !!}</p>
                @endforeach
            </div>
        @endif
    </div>
</x-graca-orientation-panel>
