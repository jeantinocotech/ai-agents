@php($chatkitSimpleChat = $chatkitSimpleChat ?? false)
<script>
window.chatApp = {
    showSuccessMessage: function (message) {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(function () { notification.remove(); }, 3000);
    },
    showErrorMessage: function (message) {
        const notification = document.createElement('div');
        notification.className =
            'fixed top-4 right-4 z-[100] max-w-md whitespace-pre-wrap break-words rounded-lg bg-red-600 px-4 py-3 text-sm text-white shadow-lg';
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(function () { notification.remove(); }, 12000);
    },
};
var chatKitBootAttempts = 0;
var chatKitMaxBootAttempts = 240;

(function loadChatKitScript() {
    if (window.__openAiChatKitScriptQueued) {
        return;
    }
    window.__openAiChatKitScriptQueued = true;
    var s = document.createElement('script');
    s.src = 'https://cdn.platform.openai.com/deployments/chatkit/chatkit.js';
    s.async = true;
    s.onload = function () {
        chatKitBootAttempts = 0;
        setTimeout(bootOpenAiChatKit, 0);
    };
    s.onerror = function () {
        window.__openAiChatKitScriptQueued = false;
        var msg =
            'Não foi possível descarregar o script ChatKit da OpenAI (CDN). Confirme rede, firewall ou extensões de bloqueio.';
        if (window.chatApp && window.chatApp.showErrorMessage) {
            window.chatApp.showErrorMessage(msg);
        }
        console.error('[ChatKit] Script CDN falhou');
        var st = document.getElementById('chatkit-documents-status');
        if (st) {
            st.textContent = msg;
        }
    };
    document.head.appendChild(s);
})();

/**
 * Assistente de CV (ChatKit sem CV/JD): debita chatkit_tokens_por_resposta após cada resposta do assistente.
 */
function initChatKitSimpleConversationBilling(chatKitEl) {
    if (!chatKitEl) {
        return;
    }
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrf ? csrf.getAttribute('content') : '';
    var agentId = {{ (int) $agent->id }};
    var cost = {{ (int) ($ckConsultTokens ?? 0) }};
    var debitUrl = @json(route('chat.chatkit.debit-consultation'));
    var lastDebitAt = 0;

    if (cost <= 0) {
        return;
    }

    function pillBalance() {
        var bal = document.querySelector('[data-live-token-balance]') || document.getElementById('token-balance-value');
        if (!bal || !bal.textContent) {
            return 0;
        }
        var cleaned = bal.textContent.replace(/\./g, '').replace(/[^\d-]/g, '');
        var n = parseInt(cleaned, 10);
        return isNaN(n) ? 0 : n;
    }

    function bumpUsed(amount) {
        var n = parseInt(amount, 10);
        if (!n || n < 1) {
            return;
        }
        var el = document.getElementById('chatkit-session-tokens-used');
        if (!el) {
            return;
        }
        var cur = parseInt(String(el.textContent || '0').replace(/\./g, '').replace(/[^\d-]/g, ''), 10) || 0;
        el.textContent = (cur + n).toLocaleString('pt-BR');
    }

    function updatePill(data) {
        if (data && typeof data.token_balance === 'number' && typeof window.setDisplayedUserTokenBalance === 'function') {
            window.setDisplayedUserTokenBalance(data.token_balance);
        }
    }

    chatKitEl.addEventListener('chatkit.response.end', function () {
        var now = Date.now();
        if (now - lastDebitAt < 2000) {
            return;
        }
        lastDebitAt = now;
        if (pillBalance() < cost) {
            if (window.chatApp && window.chatApp.showErrorMessage) {
                window.chatApp.showErrorMessage(
                    'Saldo insuficiente (são necessários pelo menos ' +
                        cost.toLocaleString('pt-BR') +
                        ' tokens por resposta do assistente).'
                );
            }
            return;
        }
        fetch(debitUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ agent_id: agentId, context: 'cv_turn' }),
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { res: res, data: data };
                });
            })
            .then(function (o) {
                if (!o.res.ok) {
                    if (o.res.status === 402 && o.data && o.data.error === 'insufficient_tokens') {
                        if (window.chatApp && window.chatApp.showErrorMessage) {
                            window.chatApp.showErrorMessage(
                                o.data.message || 'Saldo insuficiente para debitar esta resposta do assistente.'
                            );
                        }
                    } else if (window.chatApp && window.chatApp.showErrorMessage) {
                        window.chatApp.showErrorMessage(
                            (o.data && o.data.message) || 'Não foi possível atualizar o saldo de tokens.'
                        );
                    }
                    return;
                }
                updatePill(o.data);
                bumpUsed(o.data && o.data.tokens_debited);
            })
            .catch(function () {
                if (window.chatApp && window.chatApp.showErrorMessage) {
                    window.chatApp.showErrorMessage('Erro de rede ao registar tokens da conversa.');
                }
            });
    });
}

function bootOpenAiChatKit() {
    var el = document.getElementById('openai-chatkit-root');
    if (!el) {
        return;
    }
    if (typeof el.setOptions !== 'function') {
        chatKitBootAttempts += 1;
        if (chatKitBootAttempts > chatKitMaxBootAttempts) {
            var stallMsg =
                'Widget ChatKit não ficou disponível a tempo (elemento não está pronto). Recarregue a página.';
            if (window.chatApp && window.chatApp.showErrorMessage) {
                window.chatApp.showErrorMessage(stallMsg);
            }
            console.error('[ChatKit] Timeout a aguardar setOptions');
            var st = document.getElementById('chatkit-documents-status');
            if (st && !st.textContent) {
                st.textContent = stallMsg;
            }
            return;
        }
        setTimeout(bootOpenAiChatKit, 50);
        return;
    }

    if (!el.dataset.chatkitErrBound) {
        el.dataset.chatkitErrBound = '1';
        el.addEventListener(
            'chatkit.error',
            function (ev) {
                var d = ev.detail || {};
                var err = d.error || d;
                var text =
                    (err && typeof err.message === 'string' && err.message) ||
                    (typeof err === 'string' ? err : 'Erro ao iniciar o ChatKit.');
                if (window.chatApp && window.chatApp.showErrorMessage) {
                    window.chatApp.showErrorMessage(text);
                }
                console.error('[chatkit.error]', err);
                var sx = document.getElementById('chatkit-documents-status');
                if (sx && text) {
                    sx.textContent = text;
                }
            },
            false
        );
    }

    el.setOptions({
        api: {
            getClientSecret: async function (currentClientSecret) {
                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                var csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';
                if (!csrf) {
                    var m0 = 'Página sem token CSRF. Recarregue a página.';
                    if (window.chatApp && window.chatApp.showErrorMessage) {
                        window.chatApp.showErrorMessage(m0);
                    }
                    console.error('[ChatKit]', m0);
                    throw new Error(m0);
                }
                var res = await fetch('{{ route('chat.chatkit.session') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        agent_id: {{ $agent->id }},
                    }),
                });
                var raw = await res.text();
                var data = {};
                try {
                    data = raw ? JSON.parse(raw) : {};
                } catch (e) {
                    data = {};
                }
                function firstValidationMessage(obj) {
                    if (!obj || typeof obj.errors !== 'object' || obj.errors === null) {
                        return '';
                    }
                    var vals = Object.values(obj.errors);
                    if (!vals.length) {
                        return '';
                    }
                    var v0 = vals[0];
                    return Array.isArray(v0) && v0.length ? String(v0[0]) : String(v0 || '');
                }
                function buildFailMessage() {
                    if (res.status === 419) {
                        return 'Sessão expirada (CSRF). Recarregue a página e tente de novo.';
                    }
                    if (res.status === 401) {
                        return data.message || 'Não autenticado. Inicie sessão outra vez.';
                    }
                    if (res.status === 402 && data.error === 'insufficient_tokens') {
                        return 'TOKENS:' + (data.message || 'Tokens insuficientes.');
                    }
                    var v = firstValidationMessage(data);
                    if (v) {
                        return v;
                    }
                    if (data.message) {
                        return String(data.message);
                    }
                    if (res.status >= 500) {
                        return 'Erro no servidor ao criar sessão ChatKit (HTTP ' + res.status + '). Veja storage/logs/laravel.log.';
                    }
                    return 'Não foi possível iniciar o ChatKit (HTTP ' + res.status + ').';
                }
                if (!res.ok || typeof data.client_secret !== 'string' || data.client_secret === '') {
                    var msg = buildFailMessage();
                    if (msg.indexOf('TOKENS:') === 0) {
                        throw new Error(msg);
                    }
                    console.error('[ChatKit session]', res.status, raw.slice(0, 800));
                    if (window.chatApp && window.chatApp.showErrorMessage) {
                        window.chatApp.showErrorMessage(msg);
                    }
                    throw new Error(msg);
                }
                if (typeof data.token_balance === 'number' && typeof window.setDisplayedUserTokenBalance === 'function') {
                    window.setDisplayedUserTokenBalance(data.token_balance);
                }
                return data.client_secret;
            },
        },
    });
    if (typeof el.sendUserMessage === 'function') {
        initChatKitLibrarySendButtons(el);
    }
    @if ($chatkitSimpleChat)
        initChatKitSimpleConversationBilling(el);
    @endif
}

function initChatKitLibrarySendButtons(chatKitEl) {
    var cvBtn = document.getElementById('chatkit-send-cv');
    var jdBtn = document.getElementById('chatkit-send-jd');
    var cvSel = document.getElementById('chatkit-default-cv');
    var jdSel = document.getElementById('chatkit-default-jd');
    var statusEl = document.getElementById('chatkit-documents-status');
    if (!cvBtn || !jdBtn || !cvSel || !jdSel || !chatKitEl) {
        return;
    }
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrf ? csrf.getAttribute('content') : '';
    var agentId = {{ (int) $agent->id }};
    var jdContentAgentId = {{ (int) ($jdContentAgentId ?? $agent->id) }};
    var limits = { cv: {{ (int) $ckMaxCv }}, jd: {{ (int) $ckMaxJd }} };
    var chatkitConsultationCost = {{ (int) ($ckConsultTokens ?? 0) }};
    var chatkitDebitUrl = @json(route('chat.chatkit.debit-consultation'));
    var jdSendAllowed = false;
    var waitAssistantEndAfterCv = false;
    var pendingAtsDebit = false;
    var sessionTokensUsedAccum = 0;

    function bumpSessionTokensUsed(amount) {
        var n = parseInt(amount, 10);
        if (!n || n < 1) {
            return;
        }
        sessionTokensUsedAccum += n;
        var el = document.getElementById('chatkit-session-tokens-used');
        if (el) {
            el.textContent = sessionTokensUsedAccum.toLocaleString('pt-BR');
        }
    }

    function parseTokenBalanceFromPill() {
        var bal = document.querySelector('[data-live-token-balance]') || document.getElementById('token-balance-value');
        if (!bal || !bal.textContent) {
            return 0;
        }
        var cleaned = bal.textContent.replace(/\./g, '').replace(/[^\d-]/g, '');
        var n = parseInt(cleaned, 10);
        return isNaN(n) ? 0 : n;
    }

    function updateTokenPillFromJson(data) {
        if (data && typeof data.token_balance === 'number' && typeof window.setDisplayedUserTokenBalance === 'function') {
            window.setDisplayedUserTokenBalance(data.token_balance);
        }
    }

    function postChatkitConsultationDebit() {
        return fetch(chatkitDebitUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ agent_id: agentId }),
        }).then(function (res) {
            return res.json().then(function (data) {
                return { res: res, data: data };
            });
        });
    }

    function contentUrl(documentId) {
        var s = String(documentId);
        if (/^p\d+$/.test(s)) {
            return '/agents/' + agentId + '/cv-perfil/' + s.slice(1) + '/conteudo';
        }
        return '/agents/' + jdContentAgentId + '/documentos/' + documentId + '/conteudo';
    }

    function setStatus(msg) {
        if (statusEl) {
            statusEl.textContent = msg;
        }
    }

    function syncJdButton() {
        var hasJd = jdSel.value !== '';
        jdBtn.disabled = !(hasJd && jdSendAllowed);
    }

    function resetJdGate() {
        jdSendAllowed = false;
        waitAssistantEndAfterCv = false;
        syncJdButton();
    }

    cvSel.addEventListener('change', function () {
        resetJdGate();
    });
    jdSel.addEventListener('change', syncJdButton);

    chatKitEl.addEventListener('chatkit.response.end', function () {
        if (waitAssistantEndAfterCv) {
            waitAssistantEndAfterCv = false;
            jdSendAllowed = true;
        }
        if (pendingAtsDebit && chatkitConsultationCost > 0) {
            pendingAtsDebit = false;
            postChatkitConsultationDebit()
                .then(function (o) {
                    if (!o.res.ok) {
                        if (o.res.status === 402 && o.data && o.data.error === 'insufficient_tokens') {
                            if (window.chatApp && window.chatApp.showErrorMessage) {
                                window.chatApp.showErrorMessage(
                                    o.data.message || 'Saldo insuficiente para esta consulta ATS.'
                                );
                            }
                        } else if (window.chatApp && window.chatApp.showErrorMessage) {
                            window.chatApp.showErrorMessage(
                                (o.data && o.data.message) || 'Não foi possível atualizar o saldo de tokens.'
                            );
                        }
                        return;
                    }
                    updateTokenPillFromJson(o.data);
                    bumpSessionTokensUsed(o.data && o.data.tokens_debited);
                    if (o.data && o.data.tokens_debited > 0 && window.chatApp && window.chatApp.showSuccessMessage) {
                        window.chatApp.showSuccessMessage(
                            'Consulta ATS: ' +
                                o.data.tokens_debited.toLocaleString('pt-BR') +
                                ' token(s) debitados.'
                        );
                    }
                })
                .catch(function () {
                    if (window.chatApp && window.chatApp.showErrorMessage) {
                        window.chatApp.showErrorMessage('Erro de rede ao registar tokens da consulta.');
                    }
                });
        }
        syncJdButton();
    });

    cvBtn.addEventListener('click', function () {
        pendingAtsDebit = false;
        var id = cvSel.value;
        if (!id) {
            setStatus('Escolha um CV na lista.');
            if (window.chatApp && window.chatApp.showErrorMessage) {
                window.chatApp.showErrorMessage('Escolha um CV na lista.');
            }
            return;
        }
        cvBtn.disabled = true;
        setStatus('A carregar CV…');
        fetch(contentUrl(id), {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (o) {
                if (!o.ok) {
                    throw new Error((o.data && o.data.message) || 'Não foi possível carregar o CV.');
                }
                var body = o.data.body;
                if (typeof body !== 'string') {
                    throw new Error('Resposta inválida.');
                }
                if (body.length > limits.cv) {
                    throw new Error('O CV guardado excede o limite de ' + limits.cv + ' caracteres.');
                }
                var text = 'Segue o meu CV para análise:\n\n' + body;
                return chatKitEl.sendUserMessage({ text: text, newThread: true });
            })
            .then(function () {
                setStatus(
                    'Nova conversa iniciada (histórico anterior foi limpo). Aguarde o assistente para enviar o JD.'
                );
                if (window.chatApp && window.chatApp.showSuccessMessage) {
                    window.chatApp.showSuccessMessage('CV enviado — conversa reiniciada para nova análise.');
                }
                jdSendAllowed = false;
                waitAssistantEndAfterCv = true;
                syncJdButton();
            })
            .catch(function (err) {
                var msg = err && err.message ? err.message : 'Erro ao enviar CV.';
                setStatus(msg);
                if (window.chatApp && window.chatApp.showErrorMessage) {
                    window.chatApp.showErrorMessage(msg);
                }
                waitAssistantEndAfterCv = false;
                jdSendAllowed = false;
            })
            .finally(function () {
                cvBtn.disabled = false;
                syncJdButton();
            });
    });

    jdBtn.addEventListener('click', function () {
        var id = jdSel.value;
        if (!id) {
            setStatus('Escolha uma vaga (JD) na lista.');
            if (window.chatApp && window.chatApp.showErrorMessage) {
                window.chatApp.showErrorMessage('Escolha uma vaga (JD) na lista.');
            }
            return;
        }
        if (chatkitConsultationCost > 0 && parseTokenBalanceFromPill() < chatkitConsultationCost) {
            var msg =
                'Precisa de pelo menos ' +
                chatkitConsultationCost.toLocaleString('pt-BR') +
                ' tokens para esta consulta ATS.';
            setStatus(msg);
            if (window.chatApp && window.chatApp.showErrorMessage) {
                window.chatApp.showErrorMessage(msg);
            }
            return;
        }
        jdBtn.disabled = true;
        setStatus('A carregar JD…');
        fetch(contentUrl(id), {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (o) {
                if (!o.ok) {
                    throw new Error((o.data && o.data.message) || 'Não foi possível carregar a vaga.');
                }
                var body = o.data.body;
                if (typeof body !== 'string') {
                    throw new Error('Resposta inválida.');
                }
                if (body.length > limits.jd) {
                    throw new Error('A vaga guardada excede o limite de ' + limits.jd + ' caracteres.');
                }
                var text = 'Segue a descrição da vaga (JD) para análise:\n\n' + body;
                return chatKitEl.sendUserMessage({ text: text });
            })
            .then(function () {
                setStatus('JD enviado ao chat.');
                if (window.chatApp && window.chatApp.showSuccessMessage) {
                    window.chatApp.showSuccessMessage('JD enviado ao chat.');
                }
                if (chatkitConsultationCost > 0) {
                    pendingAtsDebit = true;
                }
            })
            .catch(function (err) {
                var msg = err && err.message ? err.message : 'Erro ao enviar JD.';
                setStatus(msg);
                if (window.chatApp && window.chatApp.showErrorMessage) {
                    window.chatApp.showErrorMessage(msg);
                }
                pendingAtsDebit = false;
            })
            .finally(function () {
                jdBtn.disabled = false;
                syncJdButton();
            });
    });

    syncJdButton();
}

(function initChatKitDocumentDefaults() {
    var statusEl = document.getElementById('chatkit-documents-status');
    if (!statusEl) {
        return;
    }
    var cvDefaultsUrl = @json(route('agents.documents.defaults', $agent));
    var jdDefaultsUrl = @json($jdDefaultsUrl ?? route('agents.documents.defaults', $agent));
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrf ? csrf.getAttribute('content') : '';
    var cvEl = document.getElementById('chatkit-default-cv');
    var jdEl = document.getElementById('chatkit-default-jd');
    var cvChkSave = document.getElementById('chatkit-save-default-cv');
    var jdChkSave = document.getElementById('chatkit-save-default-jd');
    var debounceCv = null;
    var debounceJd = null;

    function postDefaults(body, saveUrl) {
        statusEl.textContent = 'A guardar…';
        return fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (o) {
                if (!o.ok) {
                    var msg =
                        (o.data && (o.data.message || (o.data.errors && JSON.stringify(o.data.errors)))) ||
                        'Não foi possível guardar.';
                    statusEl.textContent = typeof msg === 'string' ? msg : 'Não foi possível guardar.';
                    if (window.chatApp && window.chatApp.showErrorMessage) {
                        window.chatApp.showErrorMessage(statusEl.textContent);
                    }
                    return;
                }
                statusEl.textContent = (o.data && o.data.message) || 'Predefinições gravadas.';
                if (window.chatApp && window.chatApp.showSuccessMessage) {
                    window.chatApp.showSuccessMessage(statusEl.textContent);
                }
            })
            .catch(function () {
                statusEl.textContent = 'Erro de rede.';
                if (window.chatApp && window.chatApp.showErrorMessage) {
                    window.chatApp.showErrorMessage('Erro de rede ao gravar predefinições.');
                }
            });
    }

    function persistCvDefault() {
        if (!cvChkSave || !cvEl) {
            return;
        }
        postDefaults({
            default_cv_document_id: cvChkSave.checked ? true : null,
        }, cvDefaultsUrl);
    }

    function persistJdDefault() {
        if (!jdChkSave || !jdEl) {
            return;
        }
        var id = jdEl.value === '' ? null : parseInt(jdEl.value, 10);
        postDefaults({
            default_jd_document_id: jdChkSave.checked ? id : null,
        }, jdDefaultsUrl);
    }

    if (cvChkSave) {
        cvChkSave.addEventListener('change', persistCvDefault);
    }
    if (jdChkSave) {
        jdChkSave.addEventListener('change', persistJdDefault);
    }
    if (cvEl && cvChkSave) {
        cvEl.addEventListener('change', function () {
            if (!cvChkSave.checked) {
                return;
            }
            clearTimeout(debounceCv);
            debounceCv = setTimeout(persistCvDefault, 450);
        });
    }
    if (jdEl && jdChkSave) {
        jdEl.addEventListener('change', function () {
            if (!jdChkSave.checked) {
                return;
            }
            clearTimeout(debounceJd);
            debounceJd = setTimeout(persistJdDefault, 450);
        });
    }

})();
</script>
