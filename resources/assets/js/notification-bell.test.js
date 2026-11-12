const {registerNotificationBell, loadNotificationBell} = require('./notification-bell');

const POPOVER_URL = '/notifications/popover';

/**
 * @param {number} unreadCount
 * @returns {{root: HTMLElement, toggle: HTMLElement, badge: HTMLElement, count: HTMLElement, list: HTMLElement}}
 */
function createBell(unreadCount) {
    const root = document.createElement('div');
    root.innerHTML = `
        <div class="dropdown">
            <button data-bs-toggle="dropdown" data-notification-bell-url="${POPOVER_URL}">
                <span class="${unreadCount > 0 ? '' : 'd-none'}" data-notification-bell-badge>
                    <span data-notification-bell-count>${unreadCount}</span>
                </span>
            </button>
            <div class="dropdown-menu"><div data-notification-bell-list><p>Loading</p></div></div>
        </div>`;
    document.body.appendChild(root);

    return {
        root,
        toggle: root.querySelector('[data-notification-bell-url]'),
        badge: root.querySelector('[data-notification-bell-badge]'),
        count: root.querySelector('[data-notification-bell-count]'),
        list: root.querySelector('[data-notification-bell-list]'),
    };
}

/**
 * @param {string} body
 * @param {number} status
 * @returns {import('vitest').Mock}
 */
function stubFetch(body, status = 200) {
    const fetchMock = vi.fn().mockResolvedValue({ok: status < 400, status, text: () => Promise.resolve(body)});
    vi.stubGlobal('fetch', fetchMock);

    return fetchMock;
}

describe('notification bell', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.unstubAllGlobals();
    });

    test('loads the popover into the list when its dropdown opens', async () => {
        // Arrange
        const fetchMock = stubFetch('<div data-notification-bell-unread-count="2"><a class="dropdown-item">Season 3 is live</a></div>');
        const {root, toggle, list} = createBell(2);
        registerNotificationBell(root);

        // Act
        toggle.dispatchEvent(new Event('show.bs.dropdown', {bubbles: true}));
        await vi.waitFor(() => expect(list.textContent).toContain('Season 3 is live'));

        // Assert
        expect(fetchMock).toHaveBeenCalledExactlyOnceWith(POPOVER_URL, expect.objectContaining({credentials: 'same-origin'}));
        expect(list.textContent).not.toContain('Loading');
    });

    test('ignores a dropdown that is not the bell', async () => {
        // Arrange
        const fetchMock = stubFetch('<div data-notification-bell-unread-count="0"></div>');
        const {root} = createBell(0);
        const otherToggle = document.createElement('button');
        root.appendChild(otherToggle);
        registerNotificationBell(root);

        // Act
        otherToggle.dispatchEvent(new Event('show.bs.dropdown', {bubbles: true}));
        root.querySelector('[data-notification-bell-url]').dispatchEvent(new Event('show.bs.dropdown', {bubbles: true}));
        await vi.waitFor(() => expect(fetchMock).toHaveBeenCalled());

        // Assert
        expect(fetchMock).toHaveBeenCalledTimes(1);
    });

    test.each([
        [5, '5', false],
        [0, '0', true],
        [150, '99+', false],
    ])('takes the unread count %i from the response, showing "%s" (hidden: %s)', async (unreadCount, expectedText, expectedHidden) => {
        // Arrange
        stubFetch(`<div data-notification-bell-unread-count="${unreadCount}"></div>`);
        const {toggle, badge, count} = createBell(3);

        // Act
        await loadNotificationBell(toggle);

        // Assert
        expect(count.textContent).toBe(expectedText);
        expect(badge.classList.contains('d-none')).toBe(expectedHidden);
    });

    test('shows the badge again when a bell at 0 gains unread notifications', async () => {
        // Arrange
        stubFetch('<div data-notification-bell-unread-count="1"></div>');
        const {toggle, badge, count} = createBell(0);

        // Act
        await loadNotificationBell(toggle);

        // Assert
        expect(badge.classList.contains('d-none')).toBe(false);
        expect(count.textContent).toBe('1');
    });

    test.each([
        ['an error status', () => stubFetch('Server error', 500)],
        ['a network failure', () => vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')))],
    ])('shows the load error and keeps the badge on %s', async (name, arrangeFetch) => {
        // Arrange
        arrangeFetch();
        const {toggle, badge, count, list} = createBell(4);

        // Act
        await loadNotificationBell(toggle);

        // Assert
        expect(list.textContent).toBe('js.notification_bell_load_error');
        expect(list.textContent).not.toContain('Server error');
        expect(count.textContent).toBe('4');
        expect(badge.classList.contains('d-none')).toBe(false);
    });
});
