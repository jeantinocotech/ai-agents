/**
 * Painel «Orientação — Graça»: persiste expandir/ocultar na conta (users.ui_preferences).
 */
function readGracaPanelPrefs() {
    const raw = window.GracaPanelPrefs;
    if (!raw || typeof raw !== 'object') {
        return {
            global_collapsed: false,
            pages: {},
            dismissed_global_prompt: false,
        };
    }
    return raw;
}

function writeGracaPanelPrefs(next) {
    window.GracaPanelPrefs = next;
}

function patchGracaPanel(payload) {
    const url = window.GracaPanelSaveUrl;
    if (!url || !window.axios) {
        return Promise.reject(new Error('GracaPanelSaveUrl indisponível.'));
    }
    return window.axios.patch(url, payload).then(({ data }) => {
        if (data && data.graca_panel) {
            writeGracaPanelPrefs(data.graca_panel);
        }
        return data;
    });
}

function shouldOfferGlobalPrompt(collapsed) {
    if (!collapsed) {
        return false;
    }
    const prefs = readGracaPanelPrefs();
    return !prefs.dismissed_global_prompt && !prefs.global_collapsed;
}

function hideGlobalPrompt(panel) {
    const prompt = panel.querySelector('[data-graca-global-prompt]');
    if (prompt) {
        prompt.classList.add('hidden');
    }
}

function showGlobalPrompt(panel) {
    const prompt = panel.querySelector('[data-graca-global-prompt]');
    if (prompt) {
        prompt.classList.remove('hidden');
    }
}

function bindGracaPanel(panel) {
    const pageKey = panel.getAttribute('data-graca-page-key');
    if (!pageKey) {
        return;
    }

    const prompt = panel.querySelector('[data-graca-global-prompt]');
    const btnPage = panel.querySelector('[data-graca-prompt-page-only]');
    const btnGlobal = panel.querySelector('[data-graca-prompt-global]');

    if (btnPage) {
        btnPage.addEventListener('click', () => {
            hideGlobalPrompt(panel);
            patchGracaPanel({
                page_key: pageKey,
                collapsed: true,
                apply_global: false,
                dismiss_global_prompt: true,
            }).catch(() => {});
        });
    }

    if (btnGlobal) {
        btnGlobal.addEventListener('click', () => {
            hideGlobalPrompt(panel);
            patchGracaPanel({
                page_key: pageKey,
                collapsed: true,
                apply_global: true,
            }).catch(() => {});
        });
    }

    panel.addEventListener('toggle', () => {
        const collapsed = !panel.open;

        patchGracaPanel({
            page_key: pageKey,
            collapsed,
        })
            .then(() => {
                if (collapsed && shouldOfferGlobalPrompt(true)) {
                    showGlobalPrompt(panel);
                } else {
                    hideGlobalPrompt(panel);
                }
            })
            .catch(() => {
                if (collapsed && shouldOfferGlobalPrompt(true)) {
                    showGlobalPrompt(panel);
                }
            });
    });
}

export function bootGracaPanels() {
    if (!window.GracaPanelSaveUrl) {
        return;
    }

    document.querySelectorAll('[data-graca-panel]').forEach((panel) => {
        bindGracaPanel(panel);
    });
}
