/** Fase 2: alertas de marcos da gamificação (polling + sininho + toasts). */

const STORAGE_KEY = 'gamification_toast_seen_ids';
const BOOT_SESSION_KEY = 'gamification_toast_session_boot';

function getSeenSet() {
    try {
        const raw = window.sessionStorage.getItem(STORAGE_KEY);
        const arr = raw ? JSON.parse(raw) : [];
        return new Set(Array.isArray(arr) ? arr : []);
    } catch (_) {
        return new Set();
    }
}

function saveSeen(ids) {
    try {
        const keep = [...ids];
        window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(keep.slice(-50)));
    } catch (_) {
        //
    }
}

function toastStackAppend(node) {
    let stack = document.getElementById('gamification-toast-stack');
    if (!stack) {
        stack = document.createElement('div');
        stack.id = 'gamification-toast-stack';
        stack.className = 'fixed bottom-20 right-4 z-[70] flex max-w-md flex-col gap-2 sm:bottom-6 sm:right-6';
        document.body.appendChild(stack);
    }
    stack.prepend(node);
}

function spawnToast(payload) {
    const el = document.createElement('div');
    el.setAttribute('role', 'status');
    el.className =
        'flex gap-3 rounded-xl border border-violet-200 bg-white p-4 shadow-lg ring-1 ring-violet-100 animate-[fadeSlide_220ms_ease-out]';
    el.style.opacity = '0';
    el.style.transform = 'translateY(12px)';
    const iconChar = typeof payload.icon_key === 'string' && payload.icon_key ? payload.icon_key : '🎯';
    const title = escapeHtml(payload.title || 'Gamificação');
    const body = escapeHtml(payload.body || '');
    const url = typeof payload.url === 'string' ? payload.url : '';
    el.innerHTML = `
        <div class="text-2xl leading-none select-none">${escapeHtml(iconChar)}</div>
        <div class="min-w-0 flex-1 pt-0.5">
            <p class="text-sm font-bold text-slate-900">${title}</p>
            <p class="mt-0.5 text-sm text-slate-600">${body}</p>
            ${url ? `<a href="${escapeAttr(url)}" class="mt-2 inline-block text-xs font-semibold text-violet-700 hover:underline">Abrir dashboard</a>` : ''}
        </div>
        <button type="button" aria-label="Fechar alerta"
            class="shrink-0 rounded-md px-2 py-1 text-sm text-slate-400 hover:bg-slate-100 hover:text-slate-700">✕</button>
    `;
    const closeBtn = el.querySelector('button');
    const remove = () => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(12px)';
        el.style.transition = 'opacity 200ms ease, transform 200ms ease';
        setTimeout(() => el.remove(), 200);
    };
    closeBtn?.addEventListener('click', remove);
    const t = window.setTimeout(remove, 9000);

    toastStackAppend(el);

    window.requestAnimationFrame(() => {
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
        el.style.transition = 'opacity 220ms ease, transform 220ms ease';
    });
    el.addEventListener('mouseenter', () => window.clearTimeout(t));
}

function escapeHtml(s) {
    return String(s)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function escapeAttr(s) {
    return escapeHtml(s).replaceAll("'", '&#039;');
}

function broadcastUnread(payload) {
    window.dispatchEvent(new CustomEvent('grato:gamification-unread', { detail: payload }));
}

export function bootGamificationLive(cfg) {
    if (!cfg?.unreadUrl) {
        return;
    }

    if (window.__GratoGamificationLiveStarted) {
        return;
    }
    window.__GratoGamificationLiveStarted = true;

    const seenToastIds = getSeenSet();

    /** @type {AbortController|null} */
    let pollCtl = null;

    const sync = async () => {
        if (pollCtl) {
            pollCtl.abort();
        }
        pollCtl = new AbortController();
        try {
            const response = await window.axios.get(cfg.unreadUrl, { signal: pollCtl.signal });
            const data = response.data || {};
            const items = data.items || [];

            if (!window.sessionStorage.getItem(BOOT_SESSION_KEY)) {
                window.sessionStorage.setItem(BOOT_SESSION_KEY, '1');
                for (const row of items) {
                    if (row.id) {
                        seenToastIds.add(row.id);
                    }
                }
                saveSeen(Array.from(seenToastIds));
                broadcastUnread({
                    count: data.count ?? 0,
                    items,
                });

                return;
            }

            broadcastUnread({
                count: data.count ?? 0,
                items,
            });
            let changed = false;
            for (const row of items) {
                const id = row.id;
                if (!id || seenToastIds.has(id)) {
                    continue;
                }
                seenToastIds.add(id);
                changed = true;
                const d = row.data || {};
                spawnToast({
                    title: d.title,
                    body: d.body,
                    icon_key: d.icon_key ?? null,
                    url: d.url ?? cfg.dashboardUrl ?? '',
                });
            }
            if (changed) {
                saveSeen(Array.from(seenToastIds));
            }
        } catch (_) {
            //
        }
    };

    void sync();

    const intervalMs = cfg.pollMs && cfg.pollMs > 5000 ? cfg.pollMs : 25000;
    window.setInterval(sync, intervalMs);

    window.addEventListener('grato:gamification-mark-read', () => {
        void sync();
    });

    injectKeyframeIfNeeded();
}

function injectKeyframeIfNeeded() {
    if (document.getElementById('gamification-keyframes')) {
        return;
    }
    const style = document.createElement('style');
    style.id = 'gamification-keyframes';
    style.textContent = `
@keyframes fadeSlide{
  from{opacity:0;transform:translateY(12px)}
  to{opacity:1;transform:translateY(0)}
}`;
    document.head.appendChild(style);
}
