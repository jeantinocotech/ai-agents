@php
    $chatkitSimpleChat = $chatkitSimpleChat ?? false;
    $compactTrailCvOnly = ($compactTrailCvOnly ?? false) === true;
    $compactTrailAts = isset($compactTrailStep) && (($compactTrailStep->slug ?? null) === 'ats') && ! $compactTrailCvOnly;
    if ($chatkitSimpleChat) {
        $ckStartGreeting = 'Descreva a sua experiência ou o que pretende no CV. Quando o assistente tiver ajudado a estruturar o texto, copie-o para a página da trilha e guarde.';
        $ckComposerPlaceholder = 'Escreva a sua mensagem…';
    } elseif ($compactTrailCvOnly) {
        $ckStartGreeting = (string) config(
            'career_trail.cv_assistant_chat_start_greeting',
            'Escolha um CV na lista acima e clique em «Enviar CV para revisão».'
        );
        $ckComposerPlaceholder = '';
    } else {
        $ckStartGreeting = 'Clique no botão «Enviar CV» e depois da resposta, clique no botão «Enviar Vaga».';
        $ckComposerPlaceholder = '';
    }
    $atsReanalyzeWorkspaceUrl = null;
    if ($compactTrailAts && request()->query('reanalyze') === '1') {
        $reanalysisId = (int) request()->query('ats_analysis_id');
        if ($reanalysisId > 0) {
            $atsReanalyzeWorkspaceUrl = route('career-trail.ats.workspace', ['analysis' => $reanalysisId]);
        }
    }

    $chatkitJdMetaById = [];
    if ($compactTrailAts && is_array($documentLibrary ?? null)) {
        foreach ($documentLibrary['jds'] ?? [] as $jdRow) {
            $jdMetaId = (int) ($jdRow['id'] ?? 0);
            if ($jdMetaId > 0) {
                $chatkitJdMetaById[$jdMetaId] = [
                    'user_cv_id' => isset($jdRow['user_cv_id']) ? (int) $jdRow['user_cv_id'] : null,
                    'title' => (string) ($jdRow['title'] ?? ''),
                    'allows_ats_flow' => (bool) ($jdRow['allows_ats_flow'] ?? false),
                    'ats_flow_block_message' => $jdRow['ats_flow_block_message'] ?? null,
                ];
            }
        }
    }
@endphp
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
/**
 * Erros do widget ChatKit que só aparecem no browser — regista em storage/logs/laravel.log (canal default).
 * Usar só mensagens curtas (nunca corpo de CV/JD).
 */
function postChatKitClientLog(payload) {
    try {
        var meta = document.querySelector('meta[name="csrf-token"]');
        var tok = meta ? meta.getAttribute('content') : '';
        if (!tok || !payload || payload.message == null || String(payload.message) === '') {
            return;
        }
        var aid = payload.agent_id;
        if (aid === undefined || aid === null || aid === '') {
            return;
        }
        var msg = String(payload.message);
        if (msg.length > 1500) {
            msg = msg.slice(0, 1500) + '…';
        }
        var src = payload.source != null ? String(payload.source).slice(0, 64) : '';
        fetch(@json(route('chat.chatkit.client-report')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': tok,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            keepalive: true,
            body: JSON.stringify({
                agent_id: aid,
                message: msg,
                source: src,
            }),
        }).catch(function () {});
    } catch (e) {}
}
var chatKitBootAttempts = 0;
var chatKitMaxBootAttempts = 240;

@if ($compactTrailAts)
window.chatkitPersistAtsAnalysis = function () {
    return Promise.reject(new Error('ChatKit ATS ainda não está pronto.'));
};
window.chatkitRefreshAtsWorkspaceCta = function () {};
@endif

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
                    window.chatApp.showErrorMessage('Erro de rede ao registrar tokens da conversa.');
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
                postChatKitClientLog({
                    agent_id: {{ (int) $agent->id }},
                    message: text,
                    source: 'chatkit.error',
                });
                var sx = document.getElementById('chatkit-documents-status');
                if (sx && text) {
                    sx.textContent = text;
                }
            },
            false
        );
    }

    el.setOptions({
        locale: 'pt',
        startScreen: {
            greeting: @json($ckStartGreeting),
        },
        composer: {
            placeholder: @json($ckComposerPlaceholder),
        },
        theme: {
            density: 'compact',
            typography: {
                baseSize: 14,
                fontFamily: 'ui-sans-serif, system-ui, sans-serif, "Figtree", "Segoe UI", sans-serif',
            },
        },
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
                    postChatKitClientLog({
                        agent_id: {{ (int) $agent->id }},
                        message: 'session_http_' + res.status + ': ' + String(msg).slice(0, 500),
                        source: 'chatkit_session',
                    });
                    throw new Error(msg);
                }
                if (typeof data.token_balance === 'number' && typeof window.setDisplayedUserTokenBalance === 'function') {
                    window.setDisplayedUserTokenBalance(data.token_balance);
                }
                return data.client_secret;
            },
        },
        @if ($compactTrailAts)
        onClientTool: function (toolCall) {
            var call = toolCall && typeof toolCall === 'object' ? toolCall : {};
            if (call.name !== 'persist_ats_analysis') {
                return { success: false, error: 'unknown_tool' };
            }
            postChatKitClientLog({
                agent_id: agentId,
                message: 'persist_ats_analysis tool invoked',
                source: 'persist_ats_analysis',
            });
            return Promise.resolve(window.chatkitPersistAtsAnalysis(call.params || {}))
                .then(function (result) {
                    if (result && typeof result === 'object') {
                        return result;
                    }
                    return { success: true };
                })
                .catch(function (err) {
                    return {
                        success: false,
                        error: (err && err.message) || 'persist_ats_analysis_failed',
                    };
                });
        },
        @endif
    });
    if (typeof el.sendUserMessage === 'function') {
        initChatKitLibrarySendButtons(el);
    }
    @if ($chatkitSimpleChat)
        initChatKitSimpleConversationBilling(el);
    @endif
}

function initChatKitLibrarySendButtons(chatKitEl) {
    var chatKitLibraryCvOnly = @json($compactTrailCvOnly ?? false);
    var chatKitTrailAts = @json($compactTrailAts ?? false);
    var cvBtn = document.getElementById('chatkit-send-cv');
    var jdBtn = document.getElementById('chatkit-send-jd');
    var cvSel = document.getElementById('chatkit-default-cv');
    var jdSel = document.getElementById('chatkit-default-jd');
    var statusEl = document.getElementById('chatkit-documents-status');
    if (!cvBtn || !cvSel || !chatKitEl) {
        return;
    }
    if (!chatKitLibraryCvOnly && (!jdBtn || !jdSel)) {
        return;
    }
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrf ? csrf.getAttribute('content') : '';
    var agentId = {{ (int) $agent->id }};
    var jdContentAgentId = {{ (int) ($jdContentAgentId ?? $agent->id) }};
    var limits = { cv: {{ (int) $ckMaxCv }}, jd: {{ (int) $ckMaxJd }} };
    var chatkitConsultationCost = {{ (int) ($ckConsultTokens ?? 0) }};
    var chatkitDebitUrl = @json(route('chat.chatkit.debit-consultation'));
    var markApplicationSubmittedUrlTemplate = @json($markApplicationSubmittedUrlTemplate ?? null);
    var jdSendAllowed = false;
    var waitAssistantEndAfterCv = false;
    var pendingAutoAtsJdAfterCv = false;
    var pendingAtsDebit = false;
    var atsAwaitingJdAnalysis = false;
    var atsJdAnalysisEndTimer = null;
    /** Assistente de CV só com biblioteca: um débito por «Enviar CV» quando o assistente termina a resposta. */
    var pendingCvTurnDebits = 0;
    var sessionTokensUsedAccum = 0;
    var atsAnalysisSyncUrl = @json($compactTrailAts ? route('chat.chatkit.ats-analysis-sync') : null);
    var atsThreadSyncUrl = @json($compactTrailAts ? route('chat.chatkit.ats-thread-sync') : null);
    var atsPairStatusUrl = @json($compactTrailAts ? route('chat.chatkit.ats-pair-status') : null);
    var atsChatKitThreadId = null;
    var chatkitJdMetaById = @json($chatkitJdMetaById ?? []);
    var atsReanalyzeMode = @json($compactTrailAts && request()->query('reanalyze') === '1');
    var atsReanalyzeWorkspaceUrl = @json($atsReanalyzeWorkspaceUrl);
    var atsBundledAutoSendDone = false;
    var atsCtaRefreshTimer = null;
    var atsChatkitToolPersisted = false;
    var atsAutoPersistTimer = null;
    var atsAutoPersistInFlight = false;
    var atsAutoPersistAttempt = 0;
    var btnClassViolet =
        'inline-flex items-center rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-violet-700';

    function parseCvSelectUserId() {
        var v = cvSel ? cvSel.value : '';
        if (!/^p\d+$/.test(String(v))) {
            return 0;
        }
        return parseInt(String(v).slice(1), 10) || 0;
    }

    function getSelectedAtsPairIds() {
        var jdId = jdSel && jdSel.value ? parseInt(String(jdSel.value).replace(/\D/g, ''), 10) || 0 : 0;
        return { jdId: jdId, userCvId: parseCvSelectUserId() };
    }

    function buildAtsMetaBlock(jdId, userCvId) {
        var meta = {};
        if (userCvId > 0) {
            meta.user_cv_id = userCvId;
        }
        if (jdId > 0) {
            meta.jd_document_id = jdId;
        }
        if (Object.keys(meta).length === 0) {
            return '';
        }
        return '\n\n[ATS_META]' + JSON.stringify(meta) + '[/ATS_META]';
    }

    function jdPairedUserCvId(jdId) {
        if (!jdId || !chatkitJdMetaById || typeof chatkitJdMetaById !== 'object') {
            return 0;
        }
        var row = chatkitJdMetaById[jdId] || chatkitJdMetaById[String(jdId)];
        if (!row || row.user_cv_id == null) {
            return 0;
        }
        return parseInt(row.user_cv_id, 10) || 0;
    }

    var atsFlowBlockedMessage = @json(\App\Models\AgentDocument::ATS_FLOW_BLOCKED_MESSAGE);

    /** Validação em tempo real no servidor (pair-status), não só meta da página em cache. */
    function fetchAtsPairFlowCheck(jdId, userCvId) {
        if (!chatKitTrailAts || !atsPairStatusUrl || jdId <= 0 || userCvId <= 0) {
            return Promise.resolve({ allowed: true });
        }
        var url =
            atsPairStatusUrl +
            '?jd_document_id=' +
            encodeURIComponent(String(jdId)) +
            '&user_cv_id=' +
            encodeURIComponent(String(userCvId));
        return fetch(url, {
            method: 'GET',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, status: res.status, data: data };
                });
            })
            .then(function (o) {
                if (!o.ok || !o.data) {
                    return {
                        allowed: false,
                        message: 'Não foi possível validar a vaga para o ATS (erro ' + (o.status || '') + ').',
                    };
                }
                if (o.data.pair_valid === false || o.data.allows_ats_flow === false) {
                    return {
                        allowed: false,
                        message: o.data.message || atsFlowBlockedMessage,
                    };
                }
                return { allowed: true };
            })
            .catch(function () {
                return {
                    allowed: false,
                    message: 'Não foi possível validar a vaga para o ATS.',
                };
            });
    }

    function validateAtsPairBeforeJdSend() {
        var pair = getSelectedAtsPairIds();
        if (pair.jdId <= 0 || pair.userCvId <= 0) {
            return 'Escolha um CV e uma vaga antes de enviar.';
        }
        var paired = jdPairedUserCvId(pair.jdId);
        if (paired > 0 && paired !== pair.userCvId) {
            return 'O CV seleccionado não corresponde a esta vaga. Escolha o CV associado à vaga ou outra vaga.';
        }
        return null;
    }

    function validateAtsPairBeforeJdSendAsync() {
        var syncErr = validateAtsPairBeforeJdSend();
        if (syncErr) {
            return Promise.resolve(syncErr);
        }
        var pair = getSelectedAtsPairIds();
        return fetchAtsPairFlowCheck(pair.jdId, pair.userCvId).then(function (check) {
            return check.allowed ? null : check.message;
        });
    }

    function updateAtsPairContextUi() {
        if (!chatKitTrailAts) {
            return;
        }
        var el = document.getElementById('chatkit-ats-pair-context');
        if (!el || !cvSel || !jdSel) {
            return;
        }
        var pair = getSelectedAtsPairIds();
        var cvOpt = cvSel.options[cvSel.selectedIndex];
        var jdOpt = jdSel.options[jdSel.selectedIndex];
        if (pair.userCvId <= 0 && pair.jdId <= 0) {
            el.classList.add('hidden');
            el.textContent = '';
            return;
        }
        el.classList.remove('hidden');
        el.innerHTML =
            '<span class="font-semibold text-slate-800">Par seleccionado:</span> ' +
            (cvOpt && cvOpt.text ? cvOpt.text.trim() : 'CV') +
            ' <span class="text-slate-400" aria-hidden="true">·</span> ' +
            (jdOpt && jdOpt.text ? jdOpt.text.trim() : 'Vaga');
    }

    function refreshAtsWorkspaceCta() {
        if (!chatKitTrailAts || !atsPairStatusUrl) {
            return;
        }
        var cta = document.getElementById('chatkit-ats-workspace-cta');
        var actions = document.getElementById('chatkit-ats-workspace-cta-actions');
        if (!cta || !actions) {
            return;
        }
        var pair = getSelectedAtsPairIds();
        if (pair.jdId <= 0 || pair.userCvId <= 0) {
            cta.classList.add('hidden');
            actions.innerHTML = '';
            return;
        }
        cta.classList.remove('hidden');
        actions.innerHTML =
            '<span class="text-[11px] text-slate-500">A carregar opções do workspace…</span>';
        var url =
            atsPairStatusUrl +
            '?jd_document_id=' +
            encodeURIComponent(String(pair.jdId)) +
            '&user_cv_id=' +
            encodeURIComponent(String(pair.userCvId));
        fetch(url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (o) {
                if (!o.ok || !o.data || o.data.pair_valid === false) {
                    var msg =
                        (o.data && o.data.message) ||
                        'Não foi possível validar o par CV/vaga para o workspace.';
                    actions.innerHTML =
                        '<p class="text-[11px] text-amber-800">' + msg + '</p>';
                    return;
                }
                var pendingKey = atsPendingStorageKey(pair.jdId, pair.userCvId);
                var hasPending = false;
                try {
                    hasPending = !!sessionStorage.getItem(pendingKey);
                } catch (e) {}

                var sessionSync = hasPending ? null : readAtsSessionSync(pair.jdId, pair.userCvId);
                var storeUrl = cta.getAttribute('data-store-url') || '';

                if (sessionSync && sessionSync.workspace_url) {
                    if (
                        !o.data.analysis_id ||
                        String(o.data.analysis_id) !== String(sessionSync.analysis_id)
                    ) {
                        clearAtsSessionForPair(pair.jdId, pair.userCvId);
                        sessionSync = null;
                        if (!hasPending) {
                            markAtsPendingChatkitSync(pair.jdId, pair.userCvId);
                            hasPending = true;
                            atsAwaitingJdAnalysis = true;
                        }
                    }
                }

                if (sessionSync && sessionSync.workspace_url) {
                    var scoreNote =
                        sessionSync.ats_score != null
                            ? ' <span class="text-slate-500">(ATS ' +
                              Math.round(Number(sessionSync.ats_score)) +
                              '%)</span>'
                            : '';
                    actions.innerHTML =
                        '<a href="' +
                        sessionSync.workspace_url +
                        '" class="' +
                        btnClassViolet +
                        '">Ver análise e ajustar CV</a>'
                    return;
                    
                }

                if (hasPending) {
                    var pendingIntro = atsReanalyzeMode
                        ? 'Reanálise em curso — a sincronizar tabela ATS…'
                        : 'Análise em curso — a sincronizar tabela ATS…';
                    actions.innerHTML =
                        '<p class="mb-2 text-[11px] leading-snug text-slate-700">' +
                        pendingIntro +
                        ' O workspace abre automaticamente quando a tabela estiver guardada.</p>';
                    return;
                }

                if (o.data.workspace_url && o.data.source === 'chatkit_tool') {
                    var chatkitScore =
                        o.data.ats_score != null
                            ? ' <span class="text-slate-500">(ATS ' +
                              Math.round(Number(o.data.ats_score)) +
                              '%)</span>'
                            : '';
                    actions.innerHTML =
                        '<a href="' +
                        o.data.workspace_url +
                        '" class="' +
                        btnClassViolet +
                        '">Ver análise e ajustar CV</a>' +
                        chatkitScore;
                    return;
                }

                if (o.data.workspace_url && o.data.source !== 'chatkit_tool') {
                    actions.innerHTML =
                        '<p class="mb-2 text-[11px] leading-snug text-slate-600">Existe uma lista automática antiga. Use o ChatKit para sincronizar a tabela actual.</p>' +
                        '<form method="post" action="' +
                        (cta.getAttribute('data-store-url') || '') +
                        '" class="inline">' +
                        '<input type="hidden" name="_token" value="' +
                        csrfToken +
                        '">' +
                        '<input type="hidden" name="jd_document_id" value="' +
                        pair.jdId +
                        '">' +
                        '<button type="submit" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Gerar lista automática (legado)</button></form>';
                    return;
                }

                actions.innerHTML =
                    '<form method="post" action="' +
                    storeUrl +
                    '" class="inline">' +
                    '<input type="hidden" name="_token" value="' +
                    csrfToken +
                    '">' +
                    '<input type="hidden" name="jd_document_id" value="' +
                    pair.jdId +
                    '">' +
                    '<button type="submit" class="' +
                    btnClassViolet +
                    '">Preparar lista de ajustes</button></form>';
            })
            .catch(function () {
                actions.innerHTML =
                    '<p class="text-[11px] text-slate-500">Não foi possível carregar o workspace.</p>';
            });
    }

    function atsPairStorageKey(jdId, userCvId) {
        return 'ats_chatkit_sync_' + jdId + '_' + userCvId;
    }

    function atsPendingStorageKey(jdId, userCvId) {
        return 'ats_pending_chatkit_' + jdId + '_' + userCvId;
    }

    function readAtsSessionSync(jdId, userCvId) {
        try {
            var raw = sessionStorage.getItem(atsPairStorageKey(jdId, userCvId));
            if (!raw) {
                return null;
            }
            var data = JSON.parse(raw);
            if (!data || !data.workspace_url || !data.analysis_id) {
                return null;
            }
            return data;
        } catch (e) {
            return null;
        }
    }

    function clearAtsSessionForPair(jdId, userCvId) {
        try {
            sessionStorage.removeItem(atsPairStorageKey(jdId, userCvId));
            sessionStorage.removeItem(atsPendingStorageKey(jdId, userCvId));
        } catch (e) {}
        atsChatkitToolPersisted = false;
    }

    function formatApiError(data, fallback) {
        if (!data) {
            return fallback;
        }
        if (data.message) {
            return String(data.message);
        }
        if (data.errors && typeof data.errors === 'object') {
            var keys = Object.keys(data.errors);
            for (var ki = 0; ki < keys.length; ki++) {
                var arr = data.errors[keys[ki]];
                if (arr && arr[0]) {
                    return String(arr[0]);
                }
            }
        }
        return fallback;
    }

    function writeAtsSessionSync(jdId, userCvId, payload) {
        try {
            sessionStorage.setItem(atsPairStorageKey(jdId, userCvId), JSON.stringify(payload));
            sessionStorage.removeItem(atsPendingStorageKey(jdId, userCvId));
        } catch (e) {}
    }

    function markAtsPendingChatkitSync(jdId, userCvId) {
        try {
            sessionStorage.setItem(atsPendingStorageKey(jdId, userCvId), String(Date.now()));
            sessionStorage.removeItem(atsPairStorageKey(jdId, userCvId));
        } catch (e) {}
    }

    function fetchDocumentBody(documentId) {
        return fetch(contentUrl(documentId), {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
        }).then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, data: data };
            });
        });
    }

    /**
     * Um único turno no chat (CV + vaga) para evitar duas tabelas ATS seguidas.
     */
    function sendAtsCvAndJdBundled(options) {
        options = options || {};
        if (atsBundledAutoSendDone) {
            return Promise.resolve();
        }
        if (!chatKitTrailAts || !cvSel || !jdSel || !chatKitEl) {
            return Promise.reject(new Error('Chat ATS não está pronto.'));
        }
        var cvId = cvSel.value;
        var jdId = jdSel.value;
        if (!cvId || !jdId) {
            return Promise.reject(new Error('Escolha CV e vaga.'));
        }
        if (chatkitConsultationCost > 0 && parseTokenBalanceFromPill() < chatkitConsultationCost) {
            var needTokens =
                'Precisa de pelo menos ' +
                chatkitConsultationCost.toLocaleString('pt-BR') +
                ' tokens para esta consulta ATS.';
            setStatus(needTokens);
            if (window.chatApp && window.chatApp.showErrorMessage) {
                window.chatApp.showErrorMessage(needTokens);
            }
            return Promise.reject(new Error(needTokens));
        }

        var pair = getSelectedAtsPairIds();
        return validateAtsPairBeforeJdSendAsync().then(function (pairErr) {
            if (pairErr) {
                setStatus(pairErr);
                if (window.chatApp && window.chatApp.showErrorMessage) {
                    window.chatApp.showErrorMessage(pairErr);
                }
                return Promise.reject(new Error(pairErr));
            }

            atsBundledAutoSendDone = true;
            atsChatkitToolPersisted = false;
            atsAutoPersistAttempt = 0;
            pendingAutoAtsJdAfterCv = false;
            waitAssistantEndAfterCv = false;
            jdSendAllowed = false;
            cvBtn.disabled = true;
            jdBtn.disabled = true;
            setStatus('A carregar CV e vaga…');

            return Promise.all([fetchDocumentBody(cvId), fetchDocumentBody(jdId)]);
        })
            .then(function (results) {
                var cvRes = results[0];
                var jdRes = results[1];
                if (!cvRes.ok) {
                    throw new Error(
                        (cvRes.data && cvRes.data.message) || 'Não foi possível carregar o CV.'
                    );
                }
                if (!jdRes.ok) {
                    throw new Error(
                        (jdRes.data && jdRes.data.message) || 'Não foi possível carregar a vaga.'
                    );
                }
                var cvBody = cvRes.data.body;
                var jdBody = jdRes.data.body;
                if (typeof cvBody !== 'string' || typeof jdBody !== 'string') {
                    throw new Error('Resposta inválida ao carregar documentos.');
                }
                if (cvBody.length > limits.cv) {
                    throw new Error('O CV excede o limite de ' + limits.cv + ' caracteres.');
                }
                if (jdBody.length > limits.jd) {
                    throw new Error('A vaga excede o limite de ' + limits.jd + ' caracteres.');
                }
                var intro = options.reanalyze
                    ? 'Reanálise ATS — segue o CV actualizado e a vaga. Responda com o ATS % e uma única tabela de keywords:\n\n'
                    : 'Análise ATS — segue o CV e a vaga. Responda com o ATS % e uma única tabela de keywords:\n\n';
                var text =
                    intro +
                    '--- CV ---\n' +
                    cvBody +
                    '\n\n--- VAGA (JD) ---\n' +
                    jdBody;
                text += buildAtsMetaBlock(pair.jdId, pair.userCvId);
                return chatKitEl.sendUserMessage({ text: text, newThread: true });
            })
            .then(function () {
                updateAtsStepUi(3);
                atsAwaitingJdAnalysis = true;
                if (pair.jdId > 0 && pair.userCvId > 0) {
                    markAtsPendingChatkitSync(pair.jdId, pair.userCvId);
                }
                scheduleAtsCtaRefresh();
                if (chatkitConsultationCost > 0) {
                    pendingAtsDebit = true;
                }
                setStatus(
                    'CV e vaga enviados numa só mensagem. Aguarde «Tabela ATS guardada» para abrir o workspace.'
                );
                if (window.chatApp && window.chatApp.showSuccessMessage) {
                    window.chatApp.showSuccessMessage(
                        options.reanalyze
                            ? 'Reanálise enviada — aguarde a nova tabela no chat.'
                            : 'CV e vaga enviados — aguarde a análise no chat.'
                    );
                }
                var jid = pair.jdId;
                if (jid > 0 && markApplicationSubmittedUrlTemplate) {
                    postMarkApplicationSubmitted(jid).catch(function (e) {
                        console.warn('[ATS] Registo de candidatura submetida:', e);
                    });
                }
            })
            .catch(function (err) {
                atsBundledAutoSendDone = false;
                var msg = err && err.message ? err.message : 'Erro ao enviar CV e vaga.';
                setStatus(msg);
                postChatKitClientLog({
                    agent_id: agentId,
                    message: String(msg).slice(0, 1500),
                    source: 'ats_bundled_send',
                });
                if (window.chatApp && window.chatApp.showErrorMessage) {
                    window.chatApp.showErrorMessage(msg);
                }
            })
            .finally(function () {
                cvBtn.disabled = false;
                syncJdButton();
            });
    }

    function parseAtsScoreFromValue(value) {
        if (value == null || value === '') {
            return null;
        }
        if (typeof value === 'number' && !isNaN(value) && value >= 0 && value <= 100) {
            return value;
        }
        var s = String(value);
        var m = s.match(/(\d+(?:[.,]\d+)?)/);
        if (!m) {
            return null;
        }
        var n = parseFloat(m[1].replace(',', '.'));
        return isNaN(n) || n < 0 || n > 100 ? null : n;
    }

    function resolveChatKitThreadId() {
        if (atsChatKitThreadId) {
            return atsChatKitThreadId;
        }
        if (!chatKitEl) {
            return null;
        }
        if (chatKitEl.threadId) {
            return String(chatKitEl.threadId);
        }
        if (typeof chatKitEl.getThreadId === 'function') {
            try {
                var tid = chatKitEl.getThreadId();
                if (tid) {
                    return String(tid);
                }
            } catch (threadErr) {}
        }
        return null;
    }

    function finishAtsSyncResponse(o, jdId, userCvId) {
        if (o.data && o.data.workspace_url && o.data.analysis_id) {
            writeAtsSessionSync(jdId, userCvId, {
                analysis_id: o.data.analysis_id,
                workspace_url: o.data.workspace_url,
                ats_score: o.data.ats_score != null ? o.data.ats_score : null,
                synced_at: Date.now(),
            });
        }
        atsReanalyzeMode = false;
        atsAwaitingJdAnalysis = false;
        atsChatkitToolPersisted = true;
        clearTimeout(atsJdAnalysisEndTimer);
        clearTimeout(atsAutoPersistTimer);
        refreshAtsWorkspaceCta();
        markAtsStepsAllComplete();
        var returnUrl =
            (o.data && o.data.workspace_url) ||
            atsReanalyzeWorkspaceUrl ||
            null;
        if (window.chatApp && window.chatApp.showSuccessMessage) {
            window.chatApp.showSuccessMessage(
                returnUrl
                    ? 'Tabela ATS guardada — a abrir o workspace…'
                    : 'Tabela ATS guardada — pode ajustar o CV no workspace.'
            );
        }
        if (returnUrl) {
            var dest =
                returnUrl + (returnUrl.indexOf('?') >= 0 ? '&' : '?') + 'synced=1';
            window.setTimeout(function () {
                window.location.href = dest;
            }, 1200);
            return {
                success: true,
                analysis_id: o.data && o.data.analysis_id,
                workspace_url: returnUrl,
                redirected: true,
            };
        }
        return {
            success: true,
            analysis_id: o.data && o.data.analysis_id,
            workspace_url: o.data && o.data.workspace_url,
        };
    }

    function syncAtsFromServerThread(attempt) {
        attempt = attempt || 0;
        if (
            !chatKitTrailAts ||
            !atsThreadSyncUrl ||
            atsChatkitToolPersisted ||
            atsAutoPersistInFlight
        ) {
            return Promise.resolve({ success: false, skipped: true });
        }
        var pair = getSelectedAtsPairIds();
        if (pair.jdId <= 0 || pair.userCvId <= 0) {
            return Promise.resolve({ success: false, skipped: true });
        }
        var threadId = resolveChatKitThreadId();
        if (!threadId) {
            if (attempt < 6) {
                clearTimeout(atsAutoPersistTimer);
                atsAutoPersistTimer = setTimeout(function () {
                    syncAtsFromServerThread(attempt + 1);
                }, 1200);
            }
            return Promise.resolve({ success: false, skipped: true });
        }

        atsAutoPersistInFlight = true;
        setStatus('A sincronizar tabela ATS com o workspace…');
        return fetch(atsThreadSyncUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                agent_id: agentId,
                thread_id: threadId,
                jd_document_id: pair.jdId,
                user_cv_id: pair.userCvId,
            }),
        })
            .then(function (res) {
                return res.text().then(function (text) {
                    var data = {};
                    try {
                        data = text ? JSON.parse(text) : {};
                    } catch (parseErr) {
                        data = {
                            message: text ? text.slice(0, 300) : 'Resposta inválida do servidor.',
                        };
                    }
                    return { ok: res.ok, status: res.status, data: data };
                });
            })
            .then(function (o) {
                atsAutoPersistInFlight = false;
                if (o.ok) {
                    var syncedScore =
                        o.data && o.data.ats_score != null ? Number(o.data.ats_score) : null;
                    var syncedItems = (o.data && o.data.items_count) || 0;
                    var scoreLooksWrong =
                        o.data &&
                        o.data.score_estimated === true &&
                        syncedScore === 0 &&
                        syncedItems >= 5 &&
                        attempt < 8;
                    if (scoreLooksWrong) {
                        postChatKitClientLog({
                            agent_id: agentId,
                            message:
                                'ats_thread_sync retry: score 0 estimated, items=' +
                                syncedItems,
                            source: 'persist_ats_analysis',
                        });
                        clearTimeout(atsAutoPersistTimer);
                        atsAutoPersistTimer = setTimeout(function () {
                            syncAtsFromServerThread(attempt + 1);
                        }, 3500);
                        return { success: false, retrying: true };
                    }
                    postChatKitClientLog({
                        agent_id: agentId,
                        message:
                            'ats_thread_sync ok items=' +
                            String(syncedItems) +
                            ' score=' +
                            String(syncedScore != null ? syncedScore : '?'),
                        source: 'persist_ats_analysis',
                    });
                    return finishAtsSyncResponse(o, pair.jdId, pair.userCvId);
                }
                if (o.status === 404 && attempt < 10) {
                    clearTimeout(atsAutoPersistTimer);
                    atsAutoPersistTimer = setTimeout(function () {
                        syncAtsFromServerThread(attempt + 1);
                    }, 2500);
                    return { success: false, retrying: true };
                }
                if (attempt >= 10) {
                    var failMsg =
                        (o.data && o.data.message) ||
                        'Não foi possível sincronizar a tabela ATS. Tente «Passar no filtro» novamente.';
                    setStatus(failMsg);
                    postChatKitClientLog({
                        agent_id: agentId,
                        message:
                            'ats_thread_sync fail_' +
                            (o.status || '') +
                            ': ' +
                            String(failMsg).slice(0, 400),
                        source: 'persist_ats_analysis',
                    });
                }
                return { success: false, error: o.data && o.data.message };
            })
            .catch(function (err) {
                atsAutoPersistInFlight = false;
                if (attempt < 10) {
                    clearTimeout(atsAutoPersistTimer);
                    atsAutoPersistTimer = setTimeout(function () {
                        syncAtsFromServerThread(attempt + 1);
                    }, 2500);
                    return { success: false, retrying: true };
                }
                return { success: false, error: err && err.message };
            });
    }

    function scheduleAtsThreadSyncFallback() {
        if (!chatKitTrailAts || atsChatkitToolPersisted) {
            return;
        }
        atsAutoPersistAttempt = 0;
        clearTimeout(atsAutoPersistTimer);
        atsAutoPersistTimer = setTimeout(function () {
            syncAtsFromServerThread(0);
        }, 2000);
    }

    chatKitEl.addEventListener('chatkit.thread.change', function (ev) {
        var detail = ev && ev.detail ? ev.detail : {};
        if (detail.threadId) {
            atsChatKitThreadId = String(detail.threadId);
        }
    });


    function normalizePersistParams(params) {
        var p = params && typeof params === 'object' ? params : {};
        var items = p.items;
        if (!Array.isArray(items)) {
            items = p.keywords || p.rows || p.table || [];
        }
        if (!Array.isArray(items)) {
            items = [];
        }
        var score =
            parseAtsScoreFromValue(p.ats_score) ??
            parseAtsScoreFromValue(p.ats_percent) ??
            parseAtsScoreFromValue(p.score) ??
            parseAtsScoreFromValue(p.ats_percentage) ??
            parseAtsScoreFromValue(p.ats_result);
        return {
            jd_document_id: p.jd_document_id,
            user_cv_id: p.user_cv_id,
            ats_score: score,
            items: items,
            raw_table_text: p.raw_table_text ? String(p.raw_table_text) : null,
        };
    }

    function scheduleAtsCtaRefresh() {
        if (!chatKitTrailAts) {
            return;
        }
        clearTimeout(atsCtaRefreshTimer);
        atsCtaRefreshTimer = setTimeout(function () {
            updateAtsPairContextUi();
            refreshAtsWorkspaceCta();
        }, 120);
    }

    if (chatKitTrailAts) {
        window.chatkitPersistAtsAnalysis = function (params) {
            var p = normalizePersistParams(params);
            var pair = getSelectedAtsPairIds();
            var jdId = parseInt(p.jd_document_id, 10) || pair.jdId;
            var userCvId = parseInt(p.user_cv_id, 10) || pair.userCvId;
            var rawTableText = p.raw_table_text ? String(p.raw_table_text).trim() : '';
            var hasItems = Array.isArray(p.items) && p.items.length > 0;
            if (!jdId || !userCvId || (!hasItems && rawTableText.length < 8)) {
                postChatKitClientLog({
                    agent_id: agentId,
                    message: 'persist_ats_analysis skipped: missing ids or table data',
                    source: 'persist_ats_analysis',
                });
                return Promise.resolve({ success: false, skipped: true });
            }
            return fetch(atsAnalysisSyncUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    jd_document_id: jdId,
                    user_cv_id: userCvId,
                    ats_score: p.ats_score != null ? p.ats_score : null,
                    items: hasItems ? p.items : [],
                    raw_table_text: rawTableText.length > 0 ? rawTableText : null,
                }),
            })
                .then(function (res) {
                    return res.text().then(function (text) {
                        var data = {};
                        try {
                            data = text ? JSON.parse(text) : {};
                        } catch (parseErr) {
                            data = { message: text ? text.slice(0, 300) : 'Resposta inválida do servidor.' };
                        }
                        return { ok: res.ok, status: res.status, data: data };
                    });
                })
                .then(function (o) {
                    if (!o.ok) {
                        var errMsg = formatApiError(
                            o.data,
                            'Não foi possível sincronizar a análise ATS (HTTP ' + (o.status || '') + ').'
                        );
                        postChatKitClientLog({
                            agent_id: agentId,
                            message:
                                'sync_fail_' +
                                (o.status || '') +
                                ': ' +
                                String(errMsg).slice(0, 1200) +
                                ' items=' +
                                p.items.length,
                            source: 'persist_ats_analysis',
                        });
                        if (window.chatApp && window.chatApp.showErrorMessage) {
                            window.chatApp.showErrorMessage(errMsg);
                        }
                        return { success: false, error: errMsg };
                    }
                    return finishAtsSyncResponse(o, jdId, userCvId);
                })
                .catch(function (err) {
                    var netMsg = (err && err.message) || 'Erro de rede ao sincronizar análise ATS.';
                    postChatKitClientLog({
                        agent_id: agentId,
                        message: String(netMsg).slice(0, 1500),
                        source: 'persist_ats_analysis',
                    });
                    return { success: false, error: netMsg };
                });
        };
        window.chatkitRefreshAtsWorkspaceCta = refreshAtsWorkspaceCta;
    }

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

    function postMarkApplicationSubmitted(jdNumericId) {
        if (!markApplicationSubmittedUrlTemplate || !jdNumericId) {
            return Promise.resolve();
        }
        var url = markApplicationSubmittedUrlTemplate.replace('__JD__', String(jdNumericId));
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({}),
        }).then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, data: data };
            });
        });
    }

    function postChatkitConsultationDebit(extra) {
        var payload = { agent_id: agentId };
        if (extra && extra.context === 'cv_turn') {
            payload.context = 'cv_turn';
        }
        return fetch(chatkitDebitUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        }).then(function (res) {
            return res.json().then(function (data) {
                return { res: res, data: data };
            });
        });
    }

    function handleChatkitDebitResult(o, opts) {
        opts = opts || {};
        var insufficientMsg = opts.insufficientMsg || 'Saldo insuficiente para registar esta interação.';
        var successLabel = opts.successLabel || 'Tokens';
        if (!o.res.ok) {
            if (o.res.status === 402 && o.data && o.data.error === 'insufficient_tokens') {
                if (window.chatApp && window.chatApp.showErrorMessage) {
                    window.chatApp.showErrorMessage(o.data.message || insufficientMsg);
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
                successLabel +
                    ': ' +
                    o.data.tokens_debited.toLocaleString('pt-BR') +
                    ' token(s) debitados.'
            );
        }
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

    function updateAtsStepUi(activeStep) {
        if (!chatKitTrailAts) {
            return;
        }
        var n = parseInt(activeStep, 10);
        if (isNaN(n) || n < 1) {
            n = 1;
        }
        if (n > 4) {
            n = 4;
        }
        for (var s = 1; s <= 3; s++) {
            var el = document.getElementById('chatkit-ats-step-' + s);
            if (!el) {
                continue;
            }
            var allDone = n >= 4;
            var active = !allDone && s === n;
            var done = allDone || s < n;
            el.className =
                'inline-flex items-center gap-1 rounded-md px-2 py-0.5 ' +
                (active
                    ? 'bg-indigo-600 text-white'
                    : done
                      ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200'
                      : 'bg-white text-slate-600 ring-1 ring-slate-200');
        }
    }

    function markAtsStepsAllComplete() {
        updateAtsStepUi(4);
    }

    function syncJdButton() {
        if (chatKitLibraryCvOnly) {
            return;
        }
        var hasJd = jdSel.value !== '';
        jdBtn.disabled = !(hasJd && jdSendAllowed);
        if (chatKitTrailAts) {
            if (jdSendAllowed && hasJd) {
                updateAtsStepUi(2);
            } else if (waitAssistantEndAfterCv) {
                updateAtsStepUi(2);
            }
        }
    }

    function resetJdGate() {
        jdSendAllowed = false;
        waitAssistantEndAfterCv = false;
        syncJdButton();
    }

    if (chatKitTrailAts) {
        updateAtsStepUi(1);
        scheduleAtsCtaRefresh();
    }

    cvSel.addEventListener('change', function () {
        resetJdGate();
        if (chatKitTrailAts) {
            updateAtsStepUi(1);
            scheduleAtsCtaRefresh();
        }
    });
    function syncAtsFlowGate() {
        if (!chatKitTrailAts || !jdSel) {
            return;
        }
        var pair = getSelectedAtsPairIds();
        if (pair.jdId <= 0 || pair.userCvId <= 0) {
            return;
        }
        fetchAtsPairFlowCheck(pair.jdId, pair.userCvId).then(function (check) {
            if (!check.allowed) {
                setStatus(check.message);
            }
        });
    }

    if (!chatKitLibraryCvOnly && jdSel) {
        jdSel.addEventListener('change', function () {
            syncJdButton();
            if (chatKitTrailAts) {
                syncAtsFlowGate();
                scheduleAtsCtaRefresh();
            }
        });
    }

    chatKitEl.addEventListener('chatkit.response.end', function () {
        if (waitAssistantEndAfterCv) {
            waitAssistantEndAfterCv = false;
            jdSendAllowed = true;
        }
        if (pendingAtsDebit && chatkitConsultationCost > 0) {
            pendingAtsDebit = false;
            postChatkitConsultationDebit()
                .then(function (o) {
                    handleChatkitDebitResult(o, {
                        insufficientMsg: 'Saldo insuficiente para esta consulta ATS.',
                        successLabel: 'Consulta ATS',
                    });
                })
                .catch(function () {
                    if (window.chatApp && window.chatApp.showErrorMessage) {
                        window.chatApp.showErrorMessage('Erro de rede ao registrar tokens da consulta.');
                    }
                });
        }

        if (chatKitLibraryCvOnly && chatkitConsultationCost > 0 && pendingCvTurnDebits > 0) {
            pendingCvTurnDebits -= 1;
            postChatkitConsultationDebit({ context: 'cv_turn' })
                .then(function (o) {
                    handleChatkitDebitResult(o, {
                        insufficientMsg: 'Saldo insuficiente para esta revisão de CV.',
                        successLabel: 'Revisão de CV',
                    });
                })
                .catch(function () {
                    if (window.chatApp && window.chatApp.showErrorMessage) {
                        window.chatApp.showErrorMessage('Erro de rede ao registrar tokens da revisão de CV.');
                    }
                });
        }

        if (chatKitTrailAts && atsAwaitingJdAnalysis) {
            clearTimeout(atsJdAnalysisEndTimer);
            atsJdAnalysisEndTimer = setTimeout(function () {
                if (!atsAwaitingJdAnalysis) {
                    return;
                }
                updateAtsStepUi(3);
                scheduleAtsCtaRefresh();
                setStatus(
                    'Tabela visível no chat. A sincronizar com o workspace…'
                );
                scheduleAtsThreadSyncFallback();
            }, 2800);
        }

        syncJdButton();
        if (
            pendingAutoAtsJdAfterCv &&
            !atsBundledAutoSendDone &&
            jdSendAllowed &&
            jdBtn &&
            !jdBtn.disabled &&
            jdSel &&
            jdSel.value
        ) {
            pendingAutoAtsJdAfterCv = false;
            setTimeout(function () {
                jdBtn.click();
            }, 0);
        }
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
        function runCvSend() {
        if (
            chatKitLibraryCvOnly &&
            chatkitConsultationCost > 0 &&
            parseTokenBalanceFromPill() < chatkitConsultationCost
        ) {
            var needCv = 'Precisa de pelo menos ' +
                chatkitConsultationCost.toLocaleString('pt-BR') +
                ' tokens para cada envio do CV ao assistente.';
            setStatus(needCv);
            if (window.chatApp && window.chatApp.showErrorMessage) {
                window.chatApp.showErrorMessage(needCv);
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
                var text = atsReanalyzeMode
                    ? 'Reanálise ATS — segue o meu CV actualizado:\n\n'
                    : 'Segue o meu CV para análise:\n\n';
                text += body;
                if (chatKitTrailAts) {
                    var cvPair = getSelectedAtsPairIds();
                    text += buildAtsMetaBlock(cvPair.jdId, cvPair.userCvId);
                }
                return chatKitEl.sendUserMessage({ text: text, newThread: true });
            })
            .then(function () {
                if (chatKitLibraryCvOnly) {
                    if (chatkitConsultationCost > 0) {
                        pendingCvTurnDebits += 1;
                    }
                    setStatus('CV enviado para revisão. Aguarde o assistente.');
                    if (window.chatApp && window.chatApp.showSuccessMessage) {
                        window.chatApp.showSuccessMessage('CV enviado — pode continuar a conversa no chat.');
                    }
                    waitAssistantEndAfterCv = false;
                    jdSendAllowed = false;
                } else {
                    setStatus(
                        'Nova conversa iniciada (histórico anterior foi limpo). Aguarde o assistente antes de clicar em «Enviar Vaga».'
                    );
                    if (window.chatApp && window.chatApp.showSuccessMessage) {
                        window.chatApp.showSuccessMessage('CV enviado — conversa reiniciada para nova análise.');
                    }
                    jdSendAllowed = false;
                    waitAssistantEndAfterCv = true;
                    syncJdButton();
                    if (chatKitTrailAts) {
                        updateAtsStepUi(2);
                        var cvPair = getSelectedAtsPairIds();
                        if (cvPair.jdId > 0 && cvPair.userCvId > 0) {
                            try {
                                sessionStorage.removeItem(atsPairStorageKey(cvPair.jdId, cvPair.userCvId));
                                if (atsReanalyzeMode) {
                                    markAtsPendingChatkitSync(cvPair.jdId, cvPair.userCvId);
                                } else {
                                    sessionStorage.removeItem(
                                        atsPendingStorageKey(cvPair.jdId, cvPair.userCvId)
                                    );
                                }
                            } catch (e) {}
                        }
                        scheduleAtsCtaRefresh();
                    }
                }
            })
            .catch(function (err) {
                var msg = err && err.message ? err.message : 'Erro ao enviar CV.';
                setStatus(msg);
                postChatKitClientLog({
                    agent_id: agentId,
                    message: String(msg).slice(0, 1500),
                    source: 'chatkit_send_cv',
                });
                if (window.chatApp && window.chatApp.showErrorMessage) {
                    window.chatApp.showErrorMessage(msg);
                }
                waitAssistantEndAfterCv = false;
                jdSendAllowed = false;
                pendingAutoAtsJdAfterCv = false;
            })
            .finally(function () {
                cvBtn.disabled = false;
                syncJdButton();
            });
        }
        if (chatKitTrailAts) {
            validateAtsPairBeforeJdSendAsync().then(function (cvFlowErr) {
                if (cvFlowErr) {
                    setStatus(cvFlowErr);
                    if (window.chatApp && window.chatApp.showErrorMessage) {
                        window.chatApp.showErrorMessage(cvFlowErr);
                    }
                    return;
                }
                runCvSend();
            });
            return;
        }
        runCvSend();
    });
    (function autoCareerTrailCvSendFromQuery() {
        try {
            var params = new URLSearchParams(window.location.search);
            if (params.get('auto_send') !== '1') {
                return;
            }
            var uid = params.get('user_cv_id');
            if (!uid) {
                return;
            }
            var wanted = 'p' + String(uid).replace(/\D/g, '');
            var ok = false;
            for (var i = 0; i < cvSel.options.length; i++) {
                if (cvSel.options[i].value === wanted) {
                    ok = true;
                    break;
                }
            }
            if (!ok) {
                setStatus('CV não encontrado na lista para envio automático.');
                return;
            }
            cvSel.value = wanted;
            cvSel.dispatchEvent(new Event('change', { bubbles: true }));
            params.delete('auto_send');
            params.delete('user_cv_id');
            var q = params.toString();
            window.history.replaceState({}, '', window.location.pathname + (q ? '?' + q : '') + window.location.hash);
            setTimeout(function () {
                cvBtn.click();
            }, 0);
        } catch (err) {
            console.error(err);
        }
    })();

    (function autoCareerTrailAtsPairFromQuery() {
        try {
            if (chatKitLibraryCvOnly || !jdSel || !cvSel || !cvBtn || !jdBtn) {
                return;
            }
            var params = new URLSearchParams(window.location.search);
            if (params.get('auto_ats_pair') !== '1') {
                return;
            }
            var isReanalyze = params.get('reanalyze') === '1';
            var jdid = params.get('jd_document_id');
            var pCv = params.get('profile_cv_id');
            if (!jdid || !pCv) {
                return;
            }
            var cvWanted = 'p' + String(pCv).replace(/\D/g, '');
            var jdOk = false;
            var cvOk = false;
            var i;
            for (i = 0; i < jdSel.options.length; i++) {
                if (jdSel.options[i].value === String(jdid)) {
                    jdOk = true;
                    break;
                }
            }
            for (i = 0; i < cvSel.options.length; i++) {
                if (cvSel.options[i].value === cvWanted) {
                    cvOk = true;
                    break;
                }
            }
            if (!jdOk || !cvOk) {
                var autoMsg =
                    'Combinação CV/vaga não encontrada para envio automático. Verifique o par na página Vagas.';
                setStatus(autoMsg);
                postChatKitClientLog({
                    agent_id: agentId,
                    message: autoMsg,
                    source: 'auto_ats_pair',
                });
                if (window.chatApp && window.chatApp.showErrorMessage) {
                    window.chatApp.showErrorMessage(autoMsg);
                }
                return;
            }
            window.chatkitSuppressDefaultPersist = true;
            jdSel.value = String(jdid);
            cvSel.value = cvWanted;
            jdSel.dispatchEvent(new Event('change', { bubbles: true }));
            cvSel.dispatchEvent(new Event('change', { bubbles: true }));
            validateAtsPairBeforeJdSendAsync().then(function (autoFlowErr) {
                if (autoFlowErr) {
                    window.chatkitSuppressDefaultPersist = false;
                    setStatus(autoFlowErr);
                    postChatKitClientLog({
                        agent_id: agentId,
                        message: autoFlowErr,
                        source: 'auto_ats_pair',
                    });
                    if (window.chatApp && window.chatApp.showErrorMessage) {
                        window.chatApp.showErrorMessage(autoFlowErr);
                    }
                    return;
                }
                params.delete('auto_ats_pair');
                params.delete('jd_document_id');
                params.delete('profile_cv_id');
                params.delete('reanalyze');
                params.delete('ats_analysis_id');
                var q = params.toString();
                window.history.replaceState(
                    {},
                    '',
                    window.location.pathname + (q ? '?' + q : '') + window.location.hash
                );
                if (isReanalyze) {
                    var rePair = {
                        jdId: parseInt(String(jdid).replace(/\D/g, ''), 10) || 0,
                        userCvId: parseInt(String(pCv).replace(/\D/g, ''), 10) || 0,
                    };
                    if (rePair.jdId > 0 && rePair.userCvId > 0) {
                        markAtsPendingChatkitSync(rePair.jdId, rePair.userCvId);
                        scheduleAtsCtaRefresh();
                        setStatus(
                            'Reanálise: aguarde a nova tabela no chat e «Tabela ATS guardada» para actualizar o workspace.'
                        );
                    }
                }
                setTimeout(function () {
                    sendAtsCvAndJdBundled({ reanalyze: isReanalyze })
                        .catch(function () {})
                        .finally(function () {
                            window.chatkitSuppressDefaultPersist = false;
                        });
                }, 400);
            });
        } catch (err) {
            window.chatkitSuppressDefaultPersist = false;
            console.error(err);
        }
    })();

    if (!chatKitLibraryCvOnly && jdBtn) {
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
        function runJdSend() {
        jdBtn.disabled = true;
        setStatus('A carregar a vaga…');
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
                var text =
                    'O CV já foi partilhado nesta conversa. Segue a vaga (JD) — responda com o ATS % e uma única tabela:\n\n';
                text += body;
                if (chatKitTrailAts) {
                    var jdPair = getSelectedAtsPairIds();
                    text += buildAtsMetaBlock(jdPair.jdId, jdPair.userCvId);
                }
                return chatKitEl.sendUserMessage({ text: text });
            })
            .then(function () {
                setStatus(
                    'Vaga enviada. Aguarde a análise no chat e depois «Tabela ATS guardada» para o passo 3.'
                );
                if (window.chatApp && window.chatApp.showSuccessMessage) {
                    window.chatApp.showSuccessMessage('Vaga enviada — aguarde a análise no chat.');
                }
                if (chatKitTrailAts) {
                    updateAtsStepUi(3);
                    atsAwaitingJdAnalysis = true;
                    var sentPair = getSelectedAtsPairIds();
                    if (sentPair.jdId > 0 && sentPair.userCvId > 0) {
                        markAtsPendingChatkitSync(sentPair.jdId, sentPair.userCvId);
                        scheduleAtsCtaRefresh();
                    }
                }
                if (chatkitConsultationCost > 0) {
                    pendingAtsDebit = true;
                }
                var jid = parseInt(String(id).replace(/\D/g, ''), 10);
                if (jid > 0 && markApplicationSubmittedUrlTemplate) {
                    postMarkApplicationSubmitted(jid).catch(function (e) {
                        console.warn('[ATS] Registo de candidatura submetida:', e);
                    });
                }
            })
            .catch(function (err) {
                var msg = err && err.message ? err.message : 'Erro ao enviar a vaga.';
                setStatus(msg);
                postChatKitClientLog({
                    agent_id: agentId,
                    message: String(msg).slice(0, 1500),
                    source: 'chatkit_send_jd',
                });
                if (window.chatApp && window.chatApp.showErrorMessage) {
                    window.chatApp.showErrorMessage(msg);
                }
                pendingAtsDebit = false;
            })
            .finally(function () {
                jdBtn.disabled = false;
                syncJdButton();
            });
        }
        if (chatKitTrailAts) {
            validateAtsPairBeforeJdSendAsync().then(function (pairErr) {
                if (pairErr) {
                    setStatus(pairErr);
                    if (window.chatApp && window.chatApp.showErrorMessage) {
                        window.chatApp.showErrorMessage(pairErr);
                    }
                    return;
                }
                runJdSend();
            });
            return;
        }
        runJdSend();
    });
    }

    syncJdButton();

    if (chatKitTrailAts) {
        var bootPair = getSelectedAtsPairIds();
        if (bootPair.jdId > 0 && bootPair.userCvId > 0) {
            try {
                if (sessionStorage.getItem(atsPendingStorageKey(bootPair.jdId, bootPair.userCvId))) {
                    atsAwaitingJdAnalysis = true;
                    scheduleAtsCtaRefresh();
                }
            } catch (bootErr) {}
        }
    }
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
                statusEl.textContent = (o.data && o.data.message) || 'Preferências salvas.';
                if (window.chatApp && window.chatApp.showSuccessMessage) {
                    window.chatApp.showSuccessMessage(statusEl.textContent);
                }
            })
            .catch(function () {
                statusEl.textContent = 'Erro de rede.';
                if (window.chatApp && window.chatApp.showErrorMessage) {
                    window.chatApp.showErrorMessage('Erro de rede ao salvar preferências.');
                }
            });
    }

    function persistCvDefault() {
        if (!cvChkSave || !cvEl || window.chatkitSuppressDefaultPersist) {
            return;
        }
        if (!cvChkSave.checked) {
            postDefaults({ default_cv_document_id: null }, cvDefaultsUrl);
            return;
        }
        var raw = String(cvEl.value || '');
        if (/^p\d+$/.test(raw)) {
            return;
        }
        var cvDocId = parseInt(raw.replace(/\D/g, ''), 10) || 0;
        if (cvDocId <= 0) {
            return;
        }
        postDefaults({ default_cv_document_id: cvDocId }, cvDefaultsUrl);
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
