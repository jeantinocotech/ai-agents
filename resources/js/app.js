import './bootstrap';

import './brazil-tax-id';
import './brazil-cep';

import Alpine from 'alpinejs';

import { bootGamificationLive } from './gamification-live';

window.Alpine = Alpine;

Alpine.data('gratoGamificationBell', () => ({
    open: false,
    unread: 0,
    items: [],
    init() {
        window.addEventListener('grato:gamification-unread', (event) => {
            this.unread = event.detail?.count ?? 0;
        });
    },
    togglePanel() {
        this.open = ! this.open;
        if (! this.open) {
            return;
        }
        this.loadRecent();
    },
    async loadRecent() {
        const url = window.GratoGamificationLive?.recentUrl;
        if (! url || ! window.axios) {
            return;
        }
        try {
            const { data } = await window.axios.get(url);
            this.items = data.items ?? [];
        } catch (_) {
            this.items = [];
        }
    },
    async markAllRead() {
        const url = window.GratoGamificationLive?.readAllUrl;
        if (! url || ! window.axios) {
            return;
        }
        try {
            await window.axios.post(url, {});
            this.open = false;
            this.unread = 0;
            window.dispatchEvent(new CustomEvent('grato:gamification-mark-read'));
        } catch (_) {
            //
        }
    },
}));

if (typeof window !== 'undefined' && window.GratoGamificationLive?.unreadUrl) {
    bootGamificationLive(window.GratoGamificationLive);
}

Alpine.start();
