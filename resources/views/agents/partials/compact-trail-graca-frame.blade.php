{{-- Caixa da Graça no topo do chat compacto da trilha (ATS, carta…) — expandir/ocultar como «Filtros» em entrevistas. --}}
@props([
    'gracaStep',
    'gracaSlot',
])

@php
    use App\Services\CareerTrailGracaMessageService;

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
        'ats' => (string) config('career_trail.ats_chat_graca_summary_line', 'Mensagem para este passo ATS (envio de CV e vaga no chat abaixo).'),
        'cover-letter' => (string) config('career_trail.cover_letter_chat_graca_summary_line', ''),
        'interviews' => (string) config('career_trail.interviews_chat_graca_summary_line', ''),
        'cv' => (string) config('career_trail.cv_assistant_chat_graca_summary_line', ''),
        default => '',
    };

    $mentor = (string) config('career_trail.mentor_label', 'Sra. Graça');

    $paragraphs = $bodies->isNotEmpty()
        ? $bodies->map(fn ($b) => trim((string) $b))->filter()
        : collect($fallback !== '' ? [$fallback] : []);

    $fullText = $paragraphs->implode("\n\n");
    $fullTextOneLine = preg_replace('/\s+/u', ' ', trim($fullText)) ?? '';
    $showExcerpt = mb_strlen($fullTextOneLine) > 100;
    $excerpt = $showExcerpt ? mb_substr($fullTextOneLine, 0, 140).'…' : $fullTextOneLine;
@endphp

<details class="group mb-4 overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100" open>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 text-left marker:content-none sm:px-5 sm:py-4 hover:bg-violet-50/35 [&::-webkit-details-marker]:hidden">
        <div class="flex min-w-0 flex-1 items-start gap-3">
            <div class="shrink-0 rounded-2xl bg-gradient-to-br from-violet-500/12 to-indigo-600/12 p-0.5 shadow-sm ring-2 ring-white">
                <x-graca-avatar size="md" class="ring-0 shadow-sm" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <span class="text-sm font-semibold text-slate-900">{{ $mentor }}</span>
                    <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-violet-900">Orientação</span>
                </div>
                @if ($summaryLine !== '')
                    <p class="mt-1 text-xs leading-snug text-slate-500">
                        {{ $summaryLine }}
                        <span class="group-open:hidden"> Clique nesta linha para expandir.</span>
                        <span class="hidden group-open:inline"> Clique em Ocultar ou na linha para recolher.</span>
                    </p>
                @endif
                @if ($showExcerpt && $excerpt !== '')
                    <p class="mt-1.5 text-[11px] leading-snug text-slate-400 group-open:hidden">
                        {{ $excerpt }}
                    </p>
                @endif
            </div>
        </div>
        <div class="flex shrink-0 items-center gap-2 self-start pt-0.5">
            <span class="hidden group-open:inline text-xs font-semibold text-violet-800 sm:inline">Ocultar</span>
            <span class="inline text-xs font-semibold text-violet-800 group-open:hidden sm:inline">Expandir</span>
            <svg class="h-5 w-5 shrink-0 text-slate-500 transition-transform duration-200 group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </div>
    </summary>

    <div class="border-t border-slate-100 bg-slate-50/50 px-4 pb-4 pt-3 sm:px-5">
        @if ($paragraphs->isNotEmpty())
            <div class="space-y-2 text-sm leading-relaxed text-slate-700">
                @foreach ($paragraphs as $para)
                    <p>{!! nl2br(e($para)) !!}</p>
                @endforeach
            </div>
        @endif
    </div>
</details>
