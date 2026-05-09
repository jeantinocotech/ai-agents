import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrf = document.head?.querySelector('meta[name="csrf-token"]');
if (csrf?.content) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.content;
}

/**
 * Formata e aplica o saldo de tokens a todos os nós da UI (faixa da trilha, chat, etc.).
 * Chamado quando o servidor devolve `token_balance` em JSON.
 */
window.setDisplayedUserTokenBalance = function (value) {
    if (typeof value !== 'number' || Number.isNaN(value)) {
        return;
    }
    const formatted = value.toLocaleString('pt-BR');
    document.querySelectorAll('[data-live-token-balance]').forEach((el) => {
        el.textContent = formatted;
    });
};
