@php
    $atsPrefillUserCvId = session('ats_prefill_user_cv_id');
    $selectedCvId = $selectedCvId ?? null;
    if ($selectedCvId === null && ! ($editingJd ?? null)) {
        $selectedCvId = $atsPrefillUserCvId ?? $defaultProfileCvId;
    }
@endphp
<div data-ats-cv-field
     data-duplicate-url="{{ route('career-trail.ats.cv.duplicate') }}"
     @if ($editingJd ?? null) data-edit-jd="{{ $editingJd->id }}" @endif
     @if ($jdListFilter !== \App\Support\AgentsDocumentTrailListFilter::OPEN) data-jd-list-filter="{{ $jdListFilter }}" @endif>
    <label class="mb-1 block text-sm font-medium text-gray-700">{{ $label ?? 'CV associado a esta vaga' }}</label>
    <div class="mt-1 flex flex-wrap items-end gap-2">
        <select name="user_cv_id" class="ats-jd-user-cv-select min-w-[12rem] flex-1 rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">— rascunho (sem CV) —</option>
            @foreach ($profileCvs as $pcv)
                <option value="{{ $pcv->id }}" @selected((int) $selectedCvId === (int) $pcv->id)>
                    {{ ($pcv->title ?: 'CV do perfil').' (#'.$pcv->id.')' }}{{ $pcv->is_default ? ' — padrão' : '' }}
                </option>
            @endforeach
        </select>
        <x-ui.button type="button" variant="secondary" size="sm" class="ats-cv-duplicate-btn" disabled>
            Duplicar
        </x-ui.button>
    </div>
    <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
</div>
