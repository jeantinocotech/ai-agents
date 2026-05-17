@props([
    'formId',
    'userCv' => null,
    'size' => 'md',
])

@php
    $userCvId = $userCv?->id;
    $exportPdfUrl = $userCvId
        ? route('career-trail.cv.export', ['userCv' => $userCvId, 'format' => 'pdf'])
        : null;
    $exportDocxUrl = $userCvId
        ? route('career-trail.cv.export', ['userCv' => $userCvId, 'format' => 'docx'])
        : null;
    $savedTitle = (string) ($userCv?->title ?? '');
    $savedBody = (string) ($userCv?->body ?? '');
@endphp

<details class="cv-export-dropdown relative inline-block text-left">
    <summary class="{{ \App\Support\UiButton::classes('outline', $size) }} list-none cursor-pointer [&::-webkit-details-marker]:hidden">
        Exportar
    </summary>
    <div class="absolute left-0 z-20 mt-1 min-w-[10rem] rounded-lg border border-slate-200 bg-white py-1 shadow-lg ring-1 ring-slate-900/5">
        <button type="button"
                class="cv-export-option block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
                data-format="pdf"
                data-export-url="{{ $exportPdfUrl }}">
            PDF
        </button>
        <button type="button"
                class="cv-export-option block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
                data-format="docx"
                data-export-url="{{ $exportDocxUrl }}">
            DOCX (Word)
        </button>
    </div>
</details>

<script>
    (function () {
        var form = document.getElementById(@json($formId));
        if (!form) return;

        var savedTitle = @json($savedTitle);
        var savedBody = @json($savedBody);
        var exportInput = form.querySelector('input[name="export_format"]');

        function isDirty() {
            var titleEl = form.querySelector('[name="title"]');
            var bodyEl = form.querySelector('[name="body"], #cv_body');
            var title = titleEl ? titleEl.value : '';
            var body = bodyEl ? bodyEl.value : '';
            return title !== savedTitle || body !== savedBody;
        }

        form.querySelectorAll('.cv-export-option').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var format = btn.getAttribute('data-format');
                var exportUrl = btn.getAttribute('data-export-url');

                if (!exportInput) {
                    exportInput = document.createElement('input');
                    exportInput.type = 'hidden';
                    exportInput.name = 'export_format';
                    form.appendChild(exportInput);
                }

                if (!exportUrl || isDirty()) {
                    exportInput.value = format;
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                    return;
                }

                window.location.href = exportUrl;
            });
        });
    })();
</script>
