import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import adminSecrets from '../../resources/js/admin-secrets.js';

const controllerSource = readFileSync(
    resolve(process.cwd(), 'app/Http/Controllers/AdminController.php'),
    'utf8'
);

/**
 * Every conflict code the controller can return on extend/revoke.
 * 401/404 codes are deliberately left out: the component falls back to its
 * generic message for those.
 *
 * @returns {string[]}
 */
function controllerConflictCodes() {
    const codes = [...controllerSource.matchAll(/'error' => '([a-z_]+)'\], 409\)/g)].map(m => m[1]);

    return [...new Set(codes)];
}

function cardHtml(id) {
    return `
        <div data-secret-id="${id}" x-data="adminSecretCard">
            <span data-poll-badge></span>
            <div data-poll-expire><span data-utc="2026-01-01T10:00:00+00:00" data-empty-label="—"></span></div>
            <span data-poll-reads>0</span>
            <div data-poll-fetches-tile data-fetch-hint="Plus de récupérations que de lectures">
                <span data-poll-fetches class="text-gray-900 dark:text-white">0</span>
            </div>
            <div data-poll-first-read class="hidden">
                <span data-poll-first-read-value><span data-utc=""></span></span>
            </div>
            <div data-poll-actions>
                <select data-extend-select><option value="24" selected>24</option></select>
                <button data-secret-id="${id}" data-extend-button>Prolonger</button>
            </div>
        </div>
    `;
}

function mount(ids = ['1']) {
    document.head.innerHTML = '<meta name="csrf-token" content="token-abc">';
    document.body.innerHTML = `
        <div id="root"
             data-extend-url="/fr/admin/secrets/__ID__/extend"
             data-revoke-url="/fr/admin/secrets/__ID__/revoke"
             data-poll-url="/fr/admin/poll"
             data-current-page="1"
             data-label-active="Actif"
             data-label-expired="Expiré"
             data-label-revoked="Révoqué"
             data-label-consumed="Consommé">${ids.map(cardHtml).join('')}</div>
    `;

    const rootEl = document.getElementById('root');
    const component = adminSecrets();
    component.$el = rootEl;
    component.init();

    return { component, rootEl };
}

function jsonResponse(body, { ok = true, status = 200 } = {}) {
    return { ok, status, json: async () => body };
}

let alpine;

beforeEach(() => {
    vi.useFakeTimers();
    vi.stubGlobal('fetch', vi.fn());

    alpine = {
        initTree: vi.fn(),
        $data: vi.fn((el) => {
            el.__state ??= { extending: false, revoking: false };

            return el.__state;
        }),
    };
    vi.stubGlobal('Alpine', alpine);
});

afterEach(() => {
    vi.unstubAllGlobals();
    vi.useRealTimers();
    document.head.innerHTML = '';
    document.body.innerHTML = '';
    delete window.translations;
});

describe('ERROR_MAP coverage', () => {
    it('finds the conflict codes in the controller', () => {
        expect(controllerConflictCodes().length).toBeGreaterThan(0);
    });

    it.each(controllerConflictCodes())('maps the "%s" conflict code to a dedicated message', async (code) => {
        const { component, rootEl } = mount();
        const button = rootEl.querySelector('[data-extend-button]');
        fetch.mockResolvedValue(jsonResponse({ error: code }, { ok: false, status: 409 }));

        await component.extend(button);

        expect(component.errorMessage).not.toBe('admin_error_extend');
        expect(component.errorMessage).not.toBe('admin_error_connection');
        expect(component.errorMessage).not.toBe(code);
        expect(component.errorMessage).toMatch(/^admin_error_/);
    });

    it('falls back to the generic message for an unmapped code', async () => {
        const { component, rootEl } = mount();
        const button = rootEl.querySelector('[data-extend-button]');
        fetch.mockResolvedValue(jsonResponse({ error: 'not_found' }, { ok: false, status: 404 }));

        await component.extend(button);

        expect(component.errorMessage).toBe('admin_error_extend');
    });
});

describe('extend', () => {
    it('posts the selected duration and refreshes the expiration date', async () => {
        const { component, rootEl } = mount();
        const button = rootEl.querySelector('[data-extend-button]');
        fetch.mockResolvedValue(jsonResponse({ success: true, expire_at: '2026-02-03T08:30:00+00:00' }));

        await component.extend(button);

        expect(fetch).toHaveBeenCalledWith('/fr/admin/secrets/1/extend', expect.objectContaining({
            method: 'POST',
            body: JSON.stringify({ hours: 24 }),
        }));

        const expireEl = rootEl.querySelector('[data-poll-expire] [data-utc]');
        expect(expireEl.dataset.utc).toBe('2026-02-03T08:30:00+00:00');
        expect(expireEl.textContent).not.toBe('');
        expect(component.errorMessage).toBe('');
        expect(alpine.$data(rootEl.querySelector('[data-secret-id="1"]')).extending).toBe(false);
    });

    it('shows the mapped message on a 409 and gives focus back to the button', async () => {
        window.translations = { admin_error_expired: 'Ce secret a expiré.' };
        const { component, rootEl } = mount();
        const button = rootEl.querySelector('[data-extend-button]');
        fetch.mockResolvedValue(jsonResponse({ error: 'expired' }, { ok: false, status: 409 }));

        await component.extend(button);

        expect(component.errorMessage).toBe('Ce secret a expiré.');
        expect(document.activeElement).toBe(button);
        expect(rootEl.querySelector('[data-poll-expire] [data-utc]').dataset.utc)
            .toBe('2026-01-01T10:00:00+00:00');
    });

    it('shows the generic connection message on a network error', async () => {
        const { component, rootEl } = mount();
        const button = rootEl.querySelector('[data-extend-button]');
        fetch.mockRejectedValue(new TypeError('Failed to fetch'));

        await component.extend(button);

        expect(component.errorMessage).toBe('admin_error_connection');
        expect(alpine.$data(rootEl.querySelector('[data-secret-id="1"]')).extending).toBe(false);
    });

    it('clears the error message after 5 seconds', async () => {
        const { component, rootEl } = mount();
        fetch.mockRejectedValue(new TypeError('Failed to fetch'));

        await component.extend(rootEl.querySelector('[data-extend-button]'));
        expect(component.errorMessage).toBe('admin_error_connection');

        vi.advanceTimersByTime(5000);
        expect(component.errorMessage).toBe('');
    });
});

describe('revoke', () => {
    it('marks the card as revoked and drops its actions', async () => {
        const { component, rootEl } = mount();
        fetch.mockResolvedValue(jsonResponse({ success: true }));

        await component.revoke('1');

        expect(fetch).toHaveBeenCalledWith('/fr/admin/secrets/1/revoke', expect.objectContaining({ method: 'POST' }));

        const card = rootEl.querySelector('[data-secret-id="1"]');
        expect(card.querySelector('[data-poll-badge]').textContent).toBe('Révoqué');
        expect(card.querySelector('[data-poll-actions]')).toBeNull();
        expect(component.errorMessage).toBe('');
    });

    it('shows the mapped message on a 409 and refreshes through a poll', async () => {
        window.translations = { admin_error_already_revoked: 'Déjà révoqué.' };
        const { component, rootEl } = mount();
        fetch
            .mockResolvedValueOnce(jsonResponse({ error: 'already_revoked' }, { ok: false, status: 409 }))
            .mockResolvedValueOnce(jsonResponse({ total: 1, secrets: [], new_cards_html: {} }));

        await component.revoke('1');

        expect(component.errorMessage).toBe('Déjà révoqué.');
        expect(fetch).toHaveBeenCalledTimes(2);
        expect(rootEl.querySelector('[data-secret-id="1"] [data-poll-actions]')).not.toBeNull();
    });

    it('shows the generic connection message on a network error', async () => {
        const { component } = mount();
        fetch.mockRejectedValue(new TypeError('Failed to fetch'));

        await component.revoke('1');

        expect(component.errorMessage).toBe('admin_error_connection');
    });

    it('does nothing when the card is gone', async () => {
        const { component } = mount();

        await component.revoke('999');

        expect(fetch).not.toHaveBeenCalled();
    });

    it('confirms through the modal and resets its state', async () => {
        const { component, rootEl } = mount();
        fetch.mockResolvedValue(jsonResponse({ success: true }));

        component.openRevokeModal(rootEl.querySelector('[data-extend-button]'));
        expect(component.showRevokeModal).toBe(true);
        expect(component.pendingRevokeId).toBe('1');

        await component.confirmRevoke();

        expect(component.showRevokeModal).toBe(false);
        expect(component.pendingRevokeId).toBeNull();
        expect(fetch).toHaveBeenCalledWith('/fr/admin/secrets/1/revoke', expect.anything());
    });
});

describe('poll', () => {
    it('sends the known ids and prepends the returned cards', async () => {
        const { component, rootEl } = mount(['1']);
        fetch.mockResolvedValue(jsonResponse({
            total: 2,
            secrets: [],
            new_cards_html: { 2: cardHtml('2') },
        }));

        await component.poll();

        const url = new URL(fetch.mock.calls[0][0]);
        expect(url.pathname).toBe('/fr/admin/poll');
        expect(url.searchParams.get('page')).toBe('1');
        expect(url.searchParams.get('known')).toBe('1');

        const ids = [...rootEl.querySelectorAll(':scope > [data-secret-id]')].map(el => el.dataset.secretId);
        expect(ids).toEqual(['2', '1']);
        expect(alpine.initTree).toHaveBeenCalledTimes(1);
        expect(rootEl.dataset.total).toBe('2');
    });

    it('does not duplicate cards: the new id joins the known list and an empty payload changes nothing', async () => {
        const { component, rootEl } = mount(['1']);
        fetch.mockResolvedValueOnce(jsonResponse({
            total: 2,
            secrets: [],
            new_cards_html: { 2: cardHtml('2') },
        }));
        await component.poll();

        fetch.mockResolvedValueOnce(jsonResponse({ total: 2, secrets: [], new_cards_html: {} }));
        await component.poll();

        expect(component.getKnownIds()).toEqual(['2', '1']);
        expect(new URL(fetch.mock.calls[1][0]).searchParams.get('known')).toBe('2,1');
        expect(rootEl.querySelectorAll(':scope > [data-secret-id]')).toHaveLength(2);
    });

    it('keeps at most one page of cards after an insertion', async () => {
        const { component } = mount(['1', '2', '3', '4', '5']);
        fetch.mockResolvedValue(jsonResponse({
            total: 6,
            secrets: [],
            new_cards_html: { 6: cardHtml('6') },
        }));

        await component.poll();

        expect(component.getKnownIds()).toEqual(['6', '1', '2', '3', '4']);
    });

    it('refreshes counters, badge and actions from the returned secrets', async () => {
        const { component, rootEl } = mount(['1']);
        fetch.mockResolvedValue(jsonResponse({
            total: 1,
            new_cards_html: {},
            secrets: [{
                id: '1',
                read_count: 2,
                fetch_count: 3,
                max_views: 5,
                first_read_at: '2026-01-02T12:00:00+00:00',
                expire_at: '2026-01-05T12:00:00+00:00',
                is_revoked: false,
                is_expired: true,
                has_reached_max_views: false,
                is_accessible: false,
            }],
        }));

        await component.poll();

        const card = rootEl.querySelector('[data-secret-id="1"]');
        expect(card.querySelector('[data-poll-badge]').textContent).toBe('Expiré');
        expect(card.querySelector('[data-poll-reads]').innerHTML).toContain('2');
        expect(card.querySelector('[data-poll-reads]').innerHTML).toContain('/ 5');
        expect(card.querySelector('[data-poll-fetches]').textContent).toBe('3');
        expect(card.querySelector('[data-poll-fetches]').classList.contains('text-amber-700')).toBe(true);
        expect(card.querySelector('[data-poll-fetches-tile]').getAttribute('title'))
            .toBe('Plus de récupérations que de lectures');
        expect(card.querySelector('[data-poll-first-read]').classList.contains('hidden')).toBe(false);
        expect(card.querySelector('[data-poll-expire] [data-utc]').dataset.utc).toBe('2026-01-05T12:00:00+00:00');
        expect(card.querySelector('[data-poll-actions]')).toBeNull();
    });

    it('swallows network errors', async () => {
        const { component, rootEl } = mount(['1']);
        fetch.mockRejectedValue(new TypeError('Failed to fetch'));

        await expect(component.poll()).resolves.toBeUndefined();
        expect(rootEl.querySelectorAll(':scope > [data-secret-id]')).toHaveLength(1);
    });

    it('polls on a timer once started', async () => {
        const { component } = mount(['1']);
        fetch.mockResolvedValue(jsonResponse({ total: 1, secrets: [], new_cards_html: {} }));

        expect(component.pollTimer).not.toBeNull();
        await vi.advanceTimersByTimeAsync(30000);
        expect(fetch).toHaveBeenCalledTimes(1);

        component.destroy();
        await vi.advanceTimersByTimeAsync(60000);
        expect(fetch).toHaveBeenCalledTimes(1);
    });
});
