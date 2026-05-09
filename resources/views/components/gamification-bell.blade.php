{{-- Marcos da gamificação: polling + sininho (+ toasts em gamification-live.js) --}}
@props(['compact' => false])

@php
    $outer = $compact
        ? 'relative block w-full sm:hidden shrink-0'
        : 'relative hidden sm:block shrink-0';
@endphp

<div {{ $attributes->class([$outer]) }} x-data="gratoGamificationBell()">
    <div @class([
        'px-4' => $compact,
    ])>
        <button
            type="button"
            class="relative inline-flex min-h-[2.5rem] items-center rounded-lg bg-[#23272a] text-gray-300 ring-1 ring-white/10 hover:bg-gray-700 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400 px-3 @if ($compact) w-full justify-between @endif"
            @click.prevent="togglePanel()"
            aria-label="Alertas da gamificação"
        >
            <span class="flex items-center gap-2">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 7.165 6 9.388 6 12v2.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 01-6 0m6 0H9"/>
                </svg>
                @if ($compact)
                    <span class="text-sm font-medium text-gray-200">Alertas de progresso</span>
                @endif
            </span>
            @if ($compact)
                <span class="tabular-nums text-xs font-semibold text-teal-300" x-show="unread > 0" x-text="unread"></span>
            @else
                <span
                    class="pointer-events-none absolute -right-1 -top-1 flex h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-teal-500 px-1 text-[0.625rem] font-bold text-black shadow ring-1 ring-teal-200"
                    x-cloak
                    x-show="unread > 0"
                    x-text="unread > 99 ? '99+' : unread"
                    style="display: none;"
                    aria-live="polite"
                ></span>
            @endif
        </button>
    </div>

    <div
        x-cloak
        x-show="open"
        @click.outside="open = false"
        style="display: none;"
        @class([
            'mt-2 w-full px-4' => $compact,
            'absolute right-0 z-[60] mt-2 w-full max-w-sm sm:w-96' => ! $compact,
        ])
    >
        <div class="rounded-xl border border-slate-200 bg-white shadow-lg ring-1 ring-black/5">
            <div class="flex items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
                <p class="text-sm font-bold text-slate-900">Marcos na trilha</p>
                <button type="button" class="text-xs font-semibold text-teal-700 hover:underline" @click.prevent="markAllRead()">
                    Limpar todas
                </button>
            </div>
            <div class="max-h-80 overflow-auto py-1">
                <p
                    x-show="items.length === 0"
                    x-cloak
                    class="px-4 py-6 text-center text-sm text-slate-500"
                    style="display: none;"
                >Sem novidades por aqui.</p>
                <template x-for="row in items" :key="row.id">
                    <div class="border-b border-slate-50 px-4 py-3 last:border-b-0">
                        <div class="flex gap-3">
                            <span class="text-xl leading-none" x-text="(row.data && row.data.icon_key) ? row.data.icon_key : '🎯'"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-900" x-text="(row.data && row.data.title) ? row.data.title : ''"></p>
                                <p class="mt-0.5 text-xs text-slate-600" x-text="(row.data && row.data.body) ? row.data.body : ''"></p>
                                <a :href="(row.data && row.data.url) ? row.data.url : '#'" class="mt-2 inline-flex text-xs font-semibold text-violet-700 hover:underline">Ver dashboard</a>
                            </div>
                            <template x-if="row.read !== true">
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-teal-500"></span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
