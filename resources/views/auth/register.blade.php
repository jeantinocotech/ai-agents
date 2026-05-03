@php
$bypassLegalReadGate = $errors->any() || filled(old('email')) || filled(old('name'));
$legalFingerprint = config('legal.privacy_policy_version').'|'.config('legal.terms_version');
@endphp

<style>
    dialog.register-legal-dialog::backdrop {
        background: rgb(15 23 42 / 0.55);
    }
</style>

<x-guest-layout>
    <form id="register-form" method="POST" action="{{ route('register') }}">
        @csrf

        <p id="register-draft-hint" class="hidden text-xs text-gray-500 mb-3">
            O nome e o e-mail são salvos apenas neste dispositivo até concluir o cadastro (as senhas nunca são salvas).
        </p>

        <!-- Name -->
        <div>
            <x-input-label for="name" value="Nome" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Senha" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirmar senha" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6 space-y-4">
            <p class="text-sm text-gray-600">
                Abra a política e os termos nos botões abaixo ou em nova aba
                (<a href="{{ route('privacidade') }}" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 hover:underline">política</a>,
                <a href="{{ route('termos-uso') }}" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 hover:underline">termos</a>) antes de aceitar.
            </p>

            <div class="flex items-start gap-2">
                <input id="accept_privacy_policy" type="checkbox" name="accept_privacy_policy" value="1"
                       class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 {{ $bypassLegalReadGate ? '' : 'opacity-70' }}"
                       @unless($bypassLegalReadGate) disabled aria-describedby="register-legal-note" @endunless
                       required @checked(old('accept_privacy_policy')) />
                <div class="text-sm text-gray-600">
                    <label for="accept_privacy_policy" class="inline">Li e aceito a</label>
                    <button type="button" id="btn-open-register-privacy" class="inline font-semibold text-indigo-600 hover:underline mx-0.5 align-baseline">
                        política de privacidade
                    </button>
                    <span class="inline">na versão em vigor.</span>
                    <p id="privacy-read-badge" class="mt-1 text-xs hidden text-emerald-700">✓ Documento consultado nesta sessão</p>
                </div>
            </div>
            <x-input-error :messages="$errors->get('accept_privacy_policy')" class="-mt-2 block" />

            <div class="flex items-start gap-2">
                <input id="accept_terms" type="checkbox" name="accept_terms" value="1"
                       class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 {{ $bypassLegalReadGate ? '' : 'opacity-70' }}"
                       @unless($bypassLegalReadGate) disabled aria-describedby="register-legal-note" @endunless
                       required @checked(old('accept_terms')) />
                <div class="text-sm text-gray-600">
                    <label for="accept_terms" class="inline">Li e aceito os</label>
                    <button type="button" id="btn-open-register-terms" class="inline font-semibold text-indigo-600 hover:underline mx-0.5 align-baseline">
                        termos de uso
                    </button>
                    <span class="inline">na versão em vigor.</span>
                    <p id="terms-read-badge" class="mt-1 text-xs hidden text-emerald-700">✓ Documento consultado nesta sessão</p>
                </div>
            </div>
            <x-input-error :messages="$errors->get('accept_terms')" class="-mt-2 block" />

            <p id="register-legal-note" class="{{ $bypassLegalReadGate ? 'hidden' : '' }} text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-2 py-1.5">
                Abra ambos os documentos (botões acima ou na janela sobreposta) para poder marcar as caixas de aceitação.
            </p>

            @if(! app()->environment(['local', 'testing']))
                <p class="text-xs text-gray-500">Use uma senha forte: pelo menos 12 caracteres com maiúsculas, números e símbolo.</p>
            @endif
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                Já tem conta?
            </a>

            <x-primary-button id="register-submit-btn" class="ms-4" :disabled="! $bypassLegalReadGate">
                Cadastrar
            </x-primary-button>
        </div>
    </form>

    <dialog id="register-modal-privacy" class="register-legal-dialog m-auto w-[min(100%,40rem)] max-h-[min(90vh,40rem)] rounded-xl border border-gray-200 bg-white p-0 shadow-2xl">
        <div class="flex max-h-[min(90vh,40rem)] flex-col">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 py-3">
                <span class="text-base font-semibold text-gray-900">Política de privacidade</span>
                <button type="button" class="register-legal-dialog-close rounded-md px-2 py-1 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900">
                    Fechar
                </button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto p-4 text-sm">
                @include('legal.partials.privacy-body')
            </div>
            <div class="shrink-0 border-t border-gray-100 bg-gray-50 px-4 py-2 text-xs text-gray-500">
                <a href="{{ route('privacidade') }}" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 hover:underline">Abrir esta página em nova aba</a>
                (útil para impressão ou leitura em tela cheia).
            </div>
        </div>
    </dialog>

    <dialog id="register-modal-terms" class="register-legal-dialog m-auto w-[min(100%,40rem)] max-h-[min(90vh,40rem)] rounded-xl border border-gray-200 bg-white p-0 shadow-2xl">
        <div class="flex max-h-[min(90vh,40rem)] flex-col">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 py-3">
                <span class="text-base font-semibold text-gray-900">Termos de uso</span>
                <button type="button" class="register-legal-dialog-close rounded-md px-2 py-1 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900">
                    Fechar
                </button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto p-4 text-sm">
                @include('legal.partials.terms-body', ['registerModal' => true, 'fromRegister' => false])
            </div>
            <div class="shrink-0 border-t border-gray-100 bg-gray-50 px-4 py-2 text-xs text-gray-500">
                <a href="{{ route('termos-uso') }}" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 hover:underline">Abrir esta página em nova aba</a>
                (útil para impressão ou leitura em tela cheia).
            </div>
        </div>
    </dialog>

    <script>
        (function () {
            const STORAGE_DRAFT = 'register_form_draft_v2';
            const STORAGE_PRIVACY = 'register_privacy_viewed_v2';
            const STORAGE_TERMS = 'register_terms_viewed_v2';
            const STORAGE_LEGAL_META = 'register_legal_bundle_v2';
            const LEGAL_FP = @json($legalFingerprint);
            const bypassGate = {{ $bypassLegalReadGate ? 'true' : 'false' }};
            const form = document.getElementById('register-form');
            if (! form) return;
            const nameEl = document.getElementById('name');
            const emailEl = document.getElementById('email');
            const cbPrivacy = document.getElementById('accept_privacy_policy');
            const cbTerms = document.getElementById('accept_terms');
            const badgePrivacy = document.getElementById('privacy-read-badge');
            const badgeTerms = document.getElementById('terms-read-badge');
            const hint = document.getElementById('register-draft-hint');
            const note = document.getElementById('register-legal-note');
            const submitBtn = document.getElementById('register-submit-btn');
            const dlgPrivacy = document.getElementById('register-modal-privacy');
            const dlgTerms = document.getElementById('register-modal-terms');
            const btnPrivacy = document.getElementById('btn-open-register-privacy');
            const btnTerms = document.getElementById('btn-open-register-terms');

            try {
                var prevLegal = sessionStorage.getItem(STORAGE_LEGAL_META);
                if (prevLegal !== null && prevLegal !== LEGAL_FP) {
                    sessionStorage.removeItem(STORAGE_PRIVACY);
                    sessionStorage.removeItem(STORAGE_TERMS);
                    sessionStorage.removeItem(STORAGE_DRAFT);
                }
                sessionStorage.setItem(STORAGE_LEGAL_META, LEGAL_FP);
            } catch (_) {}

            function saveDraft() {
                try {
                    sessionStorage.setItem(STORAGE_DRAFT, JSON.stringify({
                        name: nameEl ? nameEl.value : '',
                        email: emailEl ? emailEl.value : '',
                    }));
                    if (hint && ((nameEl && nameEl.value) || (emailEl && emailEl.value))) {
                        hint.classList.remove('hidden');
                    }
                } catch (_) {}
            }

            function loadDraft() {
                var hasSrvName = !!(nameEl && String(nameEl.defaultValue ?? '').trim() !== '');
                var hasSrvEmail = !!(emailEl && String(emailEl.defaultValue ?? '').trim() !== '');
                if (hasSrvName || hasSrvEmail) {
                    hint && hint.classList.add('hidden');
                    return;
                }
                try {
                    var raw = sessionStorage.getItem(STORAGE_DRAFT);
                    if (! raw || ! nameEl || ! emailEl) return;
                    var d = JSON.parse(raw);
                    if (typeof d.name === 'string') nameEl.value = d.name;
                    if (typeof d.email === 'string') emailEl.value = d.email;
                    if (nameEl.value || emailEl.value) hint && hint.classList.remove('hidden');
                } catch (_) {}
            }

            function privacyViewed() {
                try { sessionStorage.setItem(STORAGE_PRIVACY, '1'); } catch (_) {}
                badgePrivacy && badgePrivacy.classList.remove('hidden');
                syncLegalGate();
            }

            function termsViewed() {
                try { sessionStorage.setItem(STORAGE_TERMS, '1'); } catch (_) {}
                badgeTerms && badgeTerms.classList.remove('hidden');
                syncLegalGate();
            }

            function syncLegalGate() {
                if (bypassGate) {
                    unlockAccept();
                    cbPrivacy && (cbPrivacy.classList.remove('opacity-70'));
                    cbTerms && (cbTerms.classList.remove('opacity-70'));
                    return;
                }
                try {
                    if (sessionStorage.getItem(STORAGE_PRIVACY)) {
                        badgePrivacy && badgePrivacy.classList.remove('hidden');
                    }
                    if (sessionStorage.getItem(STORAGE_TERMS)) {
                        badgeTerms && badgeTerms.classList.remove('hidden');
                    }
                    var pv = !! sessionStorage.getItem(STORAGE_PRIVACY);
                    var tv = !! sessionStorage.getItem(STORAGE_TERMS);
                    if (pv && tv) unlockAccept(); else lockAccept();
                } catch (_) { lockAccept(); }
                if (! bypassGate && submitBtn) submitBtn.disabled = !!(cbPrivacy && cbPrivacy.disabled);
            }

            function lockAccept() {
                if (cbPrivacy) { cbPrivacy.disabled = true; cbPrivacy.classList.add('opacity-70'); cbPrivacy.checked = false; }
                if (cbTerms) { cbTerms.disabled = true; cbTerms.classList.add('opacity-70'); cbTerms.checked = false; }
                note && note.classList.remove('hidden');
                submitBtn && (submitBtn.disabled = true);
            }

            function unlockAccept() {
                if (cbPrivacy) { cbPrivacy.disabled = false; cbPrivacy.classList.remove('opacity-70'); }
                if (cbTerms) { cbTerms.disabled = false; cbTerms.classList.remove('opacity-70'); }
                note && note.classList.add('hidden');
                submitBtn && (submitBtn.disabled = false);
            }

            function persistBeforeLeaving() {
                saveDraft();
            }

            function wireDialog(dlg) {
                if (! dlg || typeof dlg.showModal !== 'function') return;
                dlg.querySelectorAll('.register-legal-dialog-close').forEach(function (btn) {
                    btn.addEventListener('click', function () { dlg.close(); }, { passive: true });
                });
                dlg.addEventListener('click', function (e) {
                    if (e.target === dlg) dlg.close();
                });
                dlg.addEventListener('cancel', function (e) {
                    e.preventDefault();
                    dlg.close();
                });
            }

            wireDialog(dlgPrivacy);
            wireDialog(dlgTerms);

            if (btnPrivacy) {
                btnPrivacy.addEventListener('click', function () {
                    persistBeforeLeaving();
                    if (dlgTerms && dlgTerms.open) dlgTerms.close();
                    if (dlgPrivacy) dlgPrivacy.showModal();
                    privacyViewed();
                }, { passive: true });
            }
            if (btnTerms) {
                btnTerms.addEventListener('click', function () {
                    persistBeforeLeaving();
                    if (dlgPrivacy && dlgPrivacy.open) dlgPrivacy.close();
                    if (dlgTerms) dlgTerms.showModal();
                    termsViewed();
                }, { passive: true });
            }

            document.addEventListener('click', function (e) {
                var t = e.target;
                if (! t || ! t.closest) return;
                var jump = t.closest('.register-open-privacy-modal');
                if (! jump) return;
                e.preventDefault();
                persistBeforeLeaving();
                if (dlgTerms && dlgTerms.open) dlgTerms.close();
                if (dlgPrivacy) dlgPrivacy.showModal();
                privacyViewed();
            }, true);

            ['input', 'blur'].forEach(function (evt) {
                nameEl && nameEl.addEventListener(evt, saveDraft, { passive: true });
                emailEl && emailEl.addEventListener(evt, saveDraft, { passive: true });
            });

            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'hidden') persistBeforeLeaving();
            }, { passive: true });
            window.addEventListener('pagehide', persistBeforeLeaving, { passive: true });

            loadDraft();
            syncLegalGate();

            window.addEventListener('pageshow', function () {
                loadDraft();
                syncLegalGate();
            }, { passive: true });

            form.addEventListener('submit', persistBeforeLeaving);
        })();
    </script>
</x-guest-layout>
