// ---------------------------------------------------------------------------
// Header notification bell
//
//     <div class="dropdown">
//         <button data-bs-toggle="dropdown" data-notification-bell-url="/notifications/popover">
//             <span data-notification-bell-badge><span data-notification-bell-count>3</span></span>
//         </button>
//         <div class="dropdown-menu"><div data-notification-bell-list></div></div>
//     </div>
//
// Every time the bell's dropdown opens, the newest notifications are fetched as
// server-rendered HTML into the list, and the badge takes the unread count the
// response carries. The badge hides at 0.
// ---------------------------------------------------------------------------

const MAX_DISPLAYED_COUNT = 99;

/**
 * @param {HTMLElement} toggle
 * @param {number} unreadCount
 * @returns {void}
 */
function updateNotificationBellBadge(toggle, unreadCount) {
    const badge = toggle.querySelector('[data-notification-bell-badge]');
    const count = toggle.querySelector('[data-notification-bell-count]');
    if (badge === null || count === null) {
        return;
    }

    count.textContent = unreadCount > MAX_DISPLAYED_COUNT ? `${MAX_DISPLAYED_COUNT}+` : `${unreadCount}`;
    badge.classList.toggle('d-none', unreadCount <= 0);
}

/**
 * @param {HTMLElement} list
 * @returns {void}
 */
function showNotificationBellError(list) {
    const message = document.createElement('p');
    message.className = 'dropdown-item-text notification_bell_note text-center small mb-0 py-2';
    message.textContent = lang.get('js.notification_bell_load_error');
    list.replaceChildren(message);
}

/**
 * Fetches the bell's notifications into its dropdown list.
 *
 * @param {HTMLElement} toggle
 * @returns {Promise<void>}
 */
async function loadNotificationBell(toggle) {
    const list = toggle.parentElement?.querySelector('[data-notification-bell-list]') ?? null;
    if (list === null) {
        return;
    }

    let html;
    try {
        const response = await fetch(toggle.dataset.notificationBellUrl, {
            credentials: 'same-origin',
            headers: {'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest'},
        });
        if (!response.ok) {
            showNotificationBellError(list);
            return;
        }
        html = await response.text();
    } catch {
        showNotificationBellError(list);
        return;
    }

    list.innerHTML = html;

    const unreadCount = parseInt(list.querySelector('[data-notification-bell-unread-count]')?.dataset.notificationBellUnreadCount ?? '', 10);
    if (!Number.isNaN(unreadCount)) {
        updateNotificationBellBadge(toggle, unreadCount);
    }
}

/**
 * Loads a bell's notifications whenever its Bootstrap dropdown inside root is about to open.
 *
 * @param {Document|HTMLElement} root
 * @returns {void}
 */
function registerNotificationBell(root) {
    root.addEventListener('show.bs.dropdown', event => {
        const toggle = event.target instanceof Element ? event.target.closest('[data-notification-bell-url]') : null;
        if (toggle !== null) {
            loadNotificationBell(toggle);
        }
    });
}

module.exports = {registerNotificationBell, loadNotificationBell, updateNotificationBellBadge};
