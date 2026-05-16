{{-- Painel colapsável «Orientação — Graça» (modelo compacto /trilha/ats). Preferência em users.ui_preferences. --}}
@props([
    'pageKey' => '',
    'subtitle' => null,
    'borderClass' => 'border-violet-200 bg-gradient-to-br from-violet-50 to-white',
    'wrapperClass' => 'mb-4',
])

@php
    use App\Support\GracaPanelPreferences;

    abort_unless($pageKey !== '' && in_array($pageKey, GracaPanelPreferences::pageKeys(), true), 500, 'pageKey inválido no painel Graça.');

    $mentor = (string) config('career_trail.mentor_label', 'Sra. Graça');
    $startCollapsed = auth()->check()
        ? GracaPanelPreferences::isCollapsed(auth()->user(), $pageKey)
        : false;
@endphp

<details
    {{ $attributes->class(['graca-orientation-panel group overflow-hidden rounded-2xl shadow-sm', $wrapperClass, $borderClass]) }}
    data-graca-panel
    data-graca-page-key="{{ $pageKey }}"
    @if (! $startCollapsed) open @endif
>
    <summary class="cursor-pointer list-none px-4 py-2.5 text-sm font-semibold text-violet-950 marker:content-none hover:bg-violet-50/40 [&::-webkit-details-marker]:hidden">
        <span class="flex items-center justify-between gap-2">
            <span class="min-w-0 flex-1">
                <span class="block sm:inline">Orientação — {{ $mentor }}</span>
                @if ($subtitle !== null && trim((string) $subtitle) !== '')
                    <span class="mt-0.5 block truncate text-xs font-normal text-slate-500 group-open:hidden sm:mt-0 sm:inline sm:pl-2">
                        {{ $subtitle }}
                    </span>
                @endif
            </span>
            <span class="shrink-0 text-xs font-normal text-violet-700">
                <span class="group-open:hidden">Mostrar</span>
                <span class="hidden group-open:inline">Ocultar</span>
            </span>
        </span>
    </summary>

    <div class="border-t border-violet-100/80 px-4 py-3 pt-3">
        {{ $slot }}
    </div>

    <div
        data-graca-global-prompt
        class="hidden border-t border-violet-100/80 bg-violet-50/60 px-4 py-3 text-sm text-violet-950"
    >
        <p class="font-medium">Manter a orientação oculta em todas as páginas da trilha?</p>
        <p class="mt-1 text-xs text-violet-900/80">Pode voltar a expandir em qualquer momento clicando em «Mostrar».</p>
        <div class="mt-3 flex flex-wrap gap-2">
            <button
                type="button"
                data-graca-prompt-page-only
                class="rounded-lg border border-violet-300 bg-white px-3 py-1.5 text-xs font-semibold text-violet-900 shadow-sm hover:bg-violet-50"
            >
                Só nesta página
            </button>
            <button
                type="button"
                data-graca-prompt-global
                class="rounded-lg bg-violet-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-violet-800"
            >
                Todas as páginas
            </button>
        </div>
    </div>
</details>
