import { fetchHeaders, t } from './utils.js';
import { startRing, resetRing } from './utils/poll-ring.js';

const POLL_INTERVAL = 30000;

const ERROR_MAP = {
    already_revoked: 'admin_error_already_revoked',
    revoked: 'admin_error_revoked',
};

const BADGE_CONFIG = {
    revoked: {
        class: 'px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        labelKey: 'labelRevoked',
    },
    expired: {
        class: 'px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        labelKey: 'labelExpired',
    },
    consumed: {
        class: 'px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300',
        labelKey: 'labelConsumed',
    },
    active: {
        class: 'px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        labelKey: 'labelActive',
    },
};

function getSecretStatus(secret) {
    if (secret.is_revoked) {
        return 'revoked';
    }
    if (secret.is_expired) {
        return 'expired';
    }
    if (secret.has_reached_max_views) {
        return 'consumed';
    }

    return 'active';
}

function esc(str) {
    const d = document.createElement('div');
    d.textContent = str;

    return d.innerHTML;
}

function formatLocal(isoString) {
    if (!isoString) {
        return '';
    }

    return new Date(isoString).toLocaleString(document.documentElement.lang || 'fr', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

function formatUtcElements(container) {
    container.querySelectorAll('[data-utc]').forEach(el => {
        el.textContent = formatLocal(el.dataset.utc);
    });
}

export default () => ({
    extendDays: '7',
    showRevokeModal: false,
    pendingRevokeId: null,
    errorMessage: '',
    pollTimer: null,

    init() {
        formatUtcElements(this.$el);
        this.startPolling();
    },

    destroy() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
        }
        resetRing();
    },

    startPolling() {
        const pollUrl = this.$el.dataset.pollUrl;
        if (!pollUrl) {
            return;
        }

        if (this.pollTimer) {
            clearInterval(this.pollTimer);
        }

        startRing(POLL_INTERVAL);
        this.pollTimer = setInterval(() => this.poll(), POLL_INTERVAL);
    },

    getKnownIds() {
        return Array.from(this.$el.querySelectorAll(':scope > [data-secret-id]'))
            .map(el => el.dataset.secretId);
    },

    async poll() {
        const pollUrl = this.$el.dataset.pollUrl;
        const page = this.$el.dataset.currentPage;
        if (!pollUrl) {
            return;
        }

        const url = new URL(pollUrl, window.location.origin);
        url.searchParams.set('page', page);
        url.searchParams.set('known', this.getKnownIds().join(','));

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (response.status === 401) {
                clearInterval(this.pollTimer);
                resetRing();
                window.location.href = window.location.pathname.replace('/dashboard', '');

                return;
            }

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            this.insertNewCards(data.new_cards_html);
            this.updateSecrets(data.secrets);
            this.$el.dataset.total = data.total;
            startRing(POLL_INTERVAL);
        } catch {
            // Network error — skip this cycle
        }
    },

    insertNewCards(newCardsHtml) {
        if (!newCardsHtml || Object.keys(newCardsHtml).length === 0) {
            return;
        }

        const firstCard = this.$el.querySelector('[data-secret-id]');
        for (const [, html] of Object.entries(newCardsHtml)) {
            const template = document.createElement('template');
            template.innerHTML = html.trim();
            const newCard = template.content.firstElementChild;
            newCard.style.opacity = '0';
            newCard.style.transform = 'translateY(-8px)';

            if (firstCard) {
                firstCard.before(newCard);
            } else {
                this.$el.append(newCard);
            }

            Alpine.initTree(newCard);
            formatUtcElements(newCard);

            requestAnimationFrame(() => {
                newCard.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                newCard.style.opacity = '1';
                newCard.style.transform = 'translateY(0)';
            });
        }

        const PAGE_SIZE = 5;
        const allCards = this.$el.querySelectorAll(':scope > [data-secret-id]');
        for (let i = PAGE_SIZE; i < allCards.length; i++) {
            allCards[i].remove();
        }
    },

    updateSecrets(secrets) {
        const labels = {
            labelActive: this.$el.dataset.labelActive,
            labelExpired: this.$el.dataset.labelExpired,
            labelRevoked: this.$el.dataset.labelRevoked,
            labelConsumed: this.$el.dataset.labelConsumed,
        };

        secrets.forEach((secret) => {
            const card = this.$el.querySelector(`[data-secret-id="${secret.id}"]`);
            if (!card) {
                return;
            }

            // Update status badge
            const badgeEl = card.querySelector('[data-poll-badge]');
            if (badgeEl) {
                const status = getSecretStatus(secret);
                const config = BADGE_CONFIG[status];
                badgeEl.innerHTML = `<span class="${config.class}">${esc(labels[config.labelKey])}</span>`;
            }

            // Update expire_at
            const expireEl = card.querySelector('[data-poll-expire] [data-utc]');
            if (expireEl) {
                expireEl.dataset.utc = secret.expire_at;
                expireEl.textContent = formatLocal(secret.expire_at);
            }

            // Update read count
            const readsEl = card.querySelector('[data-poll-reads]');
            if (readsEl) {
                let html = String(secret.read_count);
                if (secret.max_views) {
                    html += ` <span class="text-gray-400 dark:text-slate-500">/ ${secret.max_views}</span>`;
                }
                readsEl.innerHTML = html;
            }

            // Update first_read_at
            const firstReadEl = card.querySelector('[data-poll-first-read]');
            const firstReadValueEl = card.querySelector('[data-poll-first-read-value] [data-utc]');
            if (firstReadEl) {
                if (secret.first_read_at) {
                    firstReadEl.classList.remove('hidden');
                    if (firstReadValueEl) {
                        firstReadValueEl.dataset.utc = secret.first_read_at;
                        firstReadValueEl.textContent = formatLocal(secret.first_read_at);
                    }
                } else {
                    firstReadEl.classList.add('hidden');
                }
            }

            // Hide actions if no longer accessible
            const actionsEl = card.querySelector('[data-poll-actions]');
            if (actionsEl && !secret.is_accessible) {
                actionsEl.remove();
            }
        });
    },

    buildUrl(template, secretId) {
        return this.$el.dataset[template].replace('__ID__', secretId);
    },

    openRevokeModal(buttonEl) {
        this.pendingRevokeId = buttonEl.dataset.secretId;
        this.showRevokeModal = true;
    },

    closeRevokeModal() {
        this.showRevokeModal = false;
        this.pendingRevokeId = null;
    },

    async confirmRevoke() {
        if (this.pendingRevokeId) {
            const id = this.pendingRevokeId;
            this.showRevokeModal = false;
            this.pendingRevokeId = null;
            await this.revoke(id);
        }
    },

    showError(message) {
        this.errorMessage = message;

        setTimeout(() => {
            this.errorMessage = '';
        }, 5000);
    },

    async extend(buttonEl) {
        const secretId = buttonEl.dataset.secretId;
        const card = buttonEl.closest('[x-data]');
        const data = Alpine.$data(card);
        data.extending = true;

        try {
            const response = await fetch(this.buildUrl('extendUrl', secretId), {
                method: 'POST',
                headers: fetchHeaders(),
                body: JSON.stringify({ days: parseInt(this.extendDays) }),
            });

            if (response.ok) {
                const result = await response.json();
                const expireUtcEl = card.querySelector('[data-poll-expire] [data-utc]');
                if (expireUtcEl && result.expire_at) {
                    expireUtcEl.dataset.utc = result.expire_at;
                    expireUtcEl.textContent = formatLocal(result.expire_at);
                }
            } else {
                const result = await response.json().catch(() => ({}));
                this.showError(t(ERROR_MAP[result.error] || 'admin_error_extend'));
                buttonEl.focus();
            }
        } catch {
            this.showError(t('admin_error_connection'));
            buttonEl.focus();
        } finally {
            data.extending = false;
        }
    },

    async revoke(secretId) {
        const card = this.$el.querySelector(`:scope > [data-secret-id="${secretId}"]`);
        if (!card) {
            return;
        }

        const data = Alpine.$data(card);
        data.revoking = true;

        try {
            const response = await fetch(this.buildUrl('revokeUrl', secretId), {
                method: 'POST',
                headers: fetchHeaders(),
            });

            if (response.ok) {
                const badgeEl = card.querySelector('[data-poll-badge]');
                if (badgeEl) {
                    const config = BADGE_CONFIG.revoked;
                    badgeEl.innerHTML = `<span class="${config.class}">${esc(this.$el.dataset.labelRevoked)}</span>`;
                }

                const actionsEl = card.querySelector('[data-poll-actions]');
                if (actionsEl) {
                    actionsEl.remove();
                }
            } else {
                const result = await response.json().catch(() => ({}));
                this.showError(t(ERROR_MAP[result.error] || 'admin_error_revoke'));
            }
        } catch {
            this.showError(t('admin_error_connection'));
        } finally {
            data.revoking = false;
        }
    },
});
