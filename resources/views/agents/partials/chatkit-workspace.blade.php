@php
    $compactTrail = $compactTrail ?? $compactAts ?? false;
    $compactCvOnly = $compactCvOnly ?? false;
@endphp
@if ($compactTrail)
    @if ($compactCvOnly)
        {{-- Assistente de CV (trilha): só CVs registados — sem vaga (JD). --}}
        <section class="mb-3 rounded-xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/80 p-2.5 shadow-sm ring-1 ring-slate-100">
            <div class="flex flex-col gap-2">
                <div class="flex flex-col gap-2 border-b border-slate-100 pb-2 sm:flex-row sm:items-end sm:justify-between">
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-600">Currículo (CV)</span>
                            <label for="chatkit-save-default-cv" class="inline-flex cursor-pointer items-center gap-1 text-[11px] text-slate-600">
                                <input type="checkbox" id="chatkit-save-default-cv" value="1" class="h-3 w-3 shrink-0 rounded border-slate-300 text-indigo-600 focus:ring-1 focus:ring-indigo-500" @checked($ckDefCv)>
                                <span>Padrão</span>
                            </label>
                        </div>
                        <select id="chatkit-default-cv" class="w-full rounded-md border-slate-300 bg-white py-1.5 text-xs text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:max-w-xl">
                            <option value="">— escolher um CV —</option>
                            @foreach ($ckLib['cvs'] as $cv)
                                <option value="{{ $cv['id'] }}" @selected((string) ($ckDefCv ?? '') === (string) $cv['id'])>{{ $cv['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('career-trail.cv') }}"
                       class="inline-flex shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-center text-[11px] font-semibold text-slate-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50/60 hover:text-indigo-900">
                        Gerir CVs (Meu CV)
                    </a>
                </div>
                <div class="flex flex-wrap items-center gap-2 pt-0.5">
                    <button type="button" id="chatkit-send-cv" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-45">
                        Enviar CV para revisão
                    </button>
                </div>
                <p id="chatkit-documents-status" class="text-xs leading-snug text-slate-600 empty:hidden" role="status"></p>
            </div>
        </section>
    @else
        <section class="mb-3 rounded-xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/80 p-2.5 shadow-sm ring-1 ring-slate-100">
            <div class="flex flex-col gap-2">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                    <div class="min-w-0 space-y-1">
                        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-600">Currículo (CV)</span>
                            <label for="chatkit-save-default-cv" class="inline-flex cursor-pointer items-center gap-1 text-[11px] text-slate-600">
                                <input type="checkbox" id="chatkit-save-default-cv" value="1" class="h-3 w-3 shrink-0 rounded border-slate-300 text-indigo-600 focus:ring-1 focus:ring-indigo-500" @checked($ckDefCv)>
                                <span>Padrão</span>
                            </label>
                        </div>
                        <select id="chatkit-default-cv" class="w-full rounded-md border-slate-300 bg-white py-1 text-xs text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">— nenhum —</option>
                            @foreach ($ckLib['cvs'] as $cv)
                                <option value="{{ $cv['id'] }}" @selected((string) ($ckDefCv ?? '') === (string) $cv['id'])>{{ $cv['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-0 space-y-1">
                        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-600">Vaga (JD)</span>
                            <label for="chatkit-save-default-jd" class="inline-flex cursor-pointer items-center gap-1 text-[11px] text-slate-600">
                                <input type="checkbox" id="chatkit-save-default-jd" value="1" class="h-3 w-3 shrink-0 rounded border-slate-300 text-indigo-600 focus:ring-1 focus:ring-indigo-500" @checked($ckDefJd)>
                                <span>Padrão</span>
                            </label>
                        </div>
                        <select id="chatkit-default-jd" class="w-full rounded-md border-slate-300 bg-white py-1 text-xs text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">— nenhuma —</option>
                            @foreach ($ckLib['jds'] as $jd)
                                @php
                                    $jdOptLabel = $jd['title'];
                                    if (! empty($jd['paired_cv_label'])) {
                                        $jdOptLabel .= ' · CV: '.$jd['paired_cv_label'];
                                    }
                                @endphp
                                <option value="{{ $jd['id'] }}" @selected((int) $ckDefJd === (int) $jd['id'])>{{ $jdOptLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="space-y-1 border-t border-slate-100 pt-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="chatkit-send-cv" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-45">
                            Enviar CV
                        </button>
                        <button type="button" id="chatkit-send-jd" class="rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-45" disabled>
                            Enviar Vaga
                        </button>
                    </div>
                    <p id="chatkit-documents-status" class="text-xs leading-snug text-slate-600 empty:hidden" role="status"></p>
                </div>
            </div>
        </section>
    @endif
@else
<section class="mb-6 rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white via-slate-50/40 to-slate-50/80 p-5 shadow-sm ring-1 ring-slate-100">
    <div class="mb-4 flex flex-col gap-1 border-b border-slate-200/80 pb-3 sm:flex-row sm:items-center sm:justify-between">
        <h3 class="text-sm font-semibold text-slate-800">Documentos para o assistente</h3>
        <p class="text-xs text-slate-500">Escolha CV e vaga; envie na ordem indicada abaixo.</p>
    </div>
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="space-y-2">
            <label for="chatkit-default-cv" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Currículo (CV)</label>
            <label for="chatkit-save-default-cv" class="flex cursor-pointer items-center gap-2 text-xs text-slate-600">
                <input type="checkbox" id="chatkit-save-default-cv" value="1" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked($ckDefCv)>
                <span>Salvar seleção como padrão</span>
            </label>
            <select id="chatkit-default-cv" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">— nenhum —</option>
                @foreach ($ckLib['cvs'] as $cv)
                    <option value="{{ $cv['id'] }}" @selected((string) ($ckDefCv ?? '') === (string) $cv['id'])>{{ $cv['title'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="space-y-2">
            <label for="chatkit-default-jd" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Vaga (JD)</label>
            <label for="chatkit-save-default-jd" class="flex cursor-pointer items-center gap-2 text-xs text-slate-600">
                <input type="checkbox" id="chatkit-save-default-jd" value="1" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked($ckDefJd)>
                <span>Salvar seleção como padrão</span>
            </label>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-stretch">
                <select id="chatkit-default-jd" class="min-w-0 flex-1 rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">— nenhuma —</option>
                    @foreach ($ckLib['jds'] as $jd)
                        @php
                            $jdOptLabel = $jd['title'];
                            if (! empty($jd['paired_cv_label'])) {
                                $jdOptLabel .= ' · CV: '.$jd['paired_cv_label'];
                            }
                        @endphp
                        <option value="{{ $jd['id'] }}" @selected((int) $ckDefJd === (int) $jd['id'])>{{ $jdOptLabel }}</option>
                    @endforeach
                </select>
                <a href="{{ $documentsHubUrl ?? route('agents.documents.index', $agent) }}"
                   class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-2 text-center text-xs font-semibold text-slate-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50/60 hover:text-indigo-900 sm:max-w-[11rem] sm:self-auto">
                    <svg class="h-3.5 w-3.5 shrink-0 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Manter CV/JD
                </a>
            </div>
        </div>
    </div>
    <div class="mt-5 rounded-xl border border-indigo-100/80 bg-indigo-50/50 p-4 ring-1 ring-inset ring-indigo-100/60">
        <p class="text-sm font-medium text-slate-800">Enviar ao ChatKit</p>
        <p class="mt-1 text-xs leading-relaxed text-slate-600">
            Cada <strong class="text-slate-800">Enviar CV</strong> inicia uma <strong class="text-slate-800">conversa nova</strong> (o histórico anterior é limpo), para manter só o contexto desta análise. Depois aguarde o assistente e use <strong class="text-slate-800">Enviar Vaga</strong>.
        </p>
        <div class="mt-3 flex flex-wrap gap-2">
            <button type="button" id="chatkit-send-cv" class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-45">
                Enviar CV
            </button>
            <button type="button" id="chatkit-send-jd" class="rounded-xl bg-violet-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-45" disabled>
                Enviar Vaga
            </button>
        </div>
    </div>
    <p id="chatkit-documents-status" class="mt-3 min-h-[1.25rem] text-xs text-slate-600" role="status"></p>
</section>
@endif
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100">
    <openai-chatkit id="openai-chatkit-root" @class(['block w-full min-h-[26rem]' => $compactTrail, 'block w-full min-h-[28rem]' => ! $compactTrail])></openai-chatkit>
</div>
<div id="chat-rating-thumb-row" @class(['mt-2 flex flex-wrap items-center justify-end gap-1' => $compactTrail, 'mt-4 flex flex-wrap items-center justify-end gap-2' => ! $compactTrail])>
    @if(!$session->already_rated)
        <button type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                title="Gostei"
                aria-label="Avaliar positivamente"
                onclick="submitQuickThumbRating({{ $session->id }}, {{ $session->agent_id }}, 5)">
            <i class="fas fa-thumbs-up text-sm" aria-hidden="true"></i>
        </button>
        <button type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                title="Não gostei"
                aria-label="Avaliar negativamente"
                onclick="submitQuickThumbRating({{ $session->id }}, {{ $session->agent_id }}, 1)">
            <i class="fas fa-thumbs-down text-sm" aria-hidden="true"></i>
        </button>
    @else
        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800 ring-1 ring-emerald-200">Avaliado</span>
    @endif
</div>
