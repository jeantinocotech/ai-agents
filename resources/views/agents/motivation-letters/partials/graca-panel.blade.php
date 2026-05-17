@if (! empty($coverLetterStep))
    @php
        $coverLetterGracaFallback = (string) config(
            'career_trail.cover_letter_library_graca_fallback',
            'Guarde cartas por processo (vaga + CV na biblioteca ATS). Use o chat para redigir ou crie manualmente; pode editar e pesquisar na lista.'
        );
    @endphp
    <x-graca-orientation-panel :page-key="\App\Support\GracaPanelPreferences::PAGE_TRAIL_COVER_LETTER">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
            <div class="shrink-0 rounded-2xl bg-white p-1 shadow ring-1 ring-violet-100">
                <img src="{{ asset(config('career_trail.mentor_avatar', 'img/graca-avatar.png')) }}"
                     alt="{{ config('career_trail.mentor_label', 'Sra. Graça') }}"
                     class="h-16 w-16 rounded-xl object-cover" />
            </div>
            <div class="min-w-0 flex-1 text-sm leading-relaxed text-slate-700">
                <x-graca-slot
                    :placement="\App\Support\CareerTrailGracaSlots::COVER_LETTER_LIBRARY_INTRO"
                    :step="$coverLetterStep"
                    paragraph-class="text-sm leading-relaxed text-slate-700"
                    :fallback="$coverLetterGracaFallback"
                />
            </div>
        </div>
    </x-graca-orientation-panel>
@endif
