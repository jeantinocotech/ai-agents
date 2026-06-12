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
                Ao marcar as caixas abaixo, declara que leu e aceita os documentos (pode abrir em nova aba ou na janela sobreposta).
            </p>

            <div class="flex items-start gap-2 rounded-md border border-gray-100 p-3">
                <input id="accept_privacy_policy" type="checkbox" name="accept_privacy_policy" value="1"
                       class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                       required @checked(old('accept_privacy_policy')) />
                <label for="accept_privacy_policy" class="text-sm text-gray-600">
                    Li e aceito a
                    <a href="{{ route('privacidade') }}?from=register" target="_blank" rel="noopener noreferrer" class="font-semibold text-indigo-600 hover:underline">política de privacidade</a>
                    <span class="text-gray-400" aria-hidden="true">·</span>
                    <button type="button" id="btn-open-register-privacy" class="font-semibold text-indigo-600 hover:underline">ler aqui</button>
                    <span>na versão em vigor.</span>
                </label>
            </div>
            <x-input-error :messages="$errors->get('accept_privacy_policy')" class="-mt-2 block" />

            <div class="flex items-start gap-2 rounded-md border border-gray-100 p-3">
                <input id="accept_terms" type="checkbox" name="accept_terms" value="1"
                       class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                       required @checked(old('accept_terms')) />
                <label for="accept_terms" class="text-sm text-gray-600">
                    Li e aceito os
                    <a href="{{ route('termos-uso') }}?from=register" target="_blank" rel="noopener noreferrer" class="font-semibold text-indigo-600 hover:underline">termos de uso</a>
                    <span class="text-gray-400" aria-hidden="true">·</span>
                    <button type="button" id="btn-open-register-terms" class="font-semibold text-indigo-600 hover:underline">ler aqui</button>
                    <span>na versão em vigor.</span>
                </label>
            </div>
            <x-input-error :messages="$errors->get('accept_terms')" class="-mt-2 block" />

            @if(! app()->environment(['local', 'testing']))
                <p class="text-xs text-gray-500">Use uma senha forte: pelo menos 12 caracteres com maiúsculas, números e símbolo.</p>
            @endif
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                Já tem conta?
            </a>

            <x-primary-button class="ms-4">
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
                <a href="{{ route('privacidade') }}?from=register" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 hover:underline">Abrir esta página em nova aba</a>
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
                <a href="{{ route('termos-uso') }}?from=register" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 hover:underline">Abrir esta página em nova aba</a>
                (útil para impressão ou leitura em tela cheia).
            </div>
        </div>
    </dialog>

    <script>
        (function () {
            const STORAGE_DRAFT = 'register_form_draft_v2';
            const form = document.getElementById('register-form');
            if (! form) return;
            const nameEl = document.getElementById('name');
            const emailEl = document.getElementById('email');
            const hint = document.getElementById('register-draft-hint');
            const dlgPrivacy = document.getElementById('register-modal-privacy');
            const dlgTerms = document.getElementById('register-modal-terms');
            const btnPrivacy = document.getElementById('btn-open-register-privacy');
            const btnTerms = document.getElementById('btn-open-register-terms');

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
                }, { passive: true });
            }
            if (btnTerms) {
                btnTerms.addEventListener('click', function () {
                    persistBeforeLeaving();
                    if (dlgPrivacy && dlgPrivacy.open) dlgPrivacy.close();
                    if (dlgTerms) dlgTerms.showModal();
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

            window.addEventListener('pageshow', function () {
                loadDraft();
            }, { passive: true });

            form.addEventListener('submit', persistBeforeLeaving);
        })();
    </script>
</x-guest-layout>
