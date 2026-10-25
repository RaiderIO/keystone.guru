// ---------------------------------------------------------------------------
// Leaving-page links
//
//     <a href="https://example.com/" data-leaving-href="https://keystone.guru/user/x/link/1" target="_blank">
//
// The href stays the real destination, so hovering the link shows where it goes
// (status bar, "copy link address"). Activating it - a click, Enter, or a middle
// click - opens the data-leaving-href page in a new tab instead. Without
// JavaScript the link goes straight to its destination.
// ---------------------------------------------------------------------------

const LEFT_BUTTON = 0;
const MIDDLE_BUTTON = 1;

/**
 * Opens the leaving page of the activated link, if it has one, instead of its destination.
 *
 * @param {MouseEvent} event
 * @returns {void}
 */
function openLeavingPage(event) {
    if (event.button !== LEFT_BUTTON && event.button !== MIDDLE_BUTTON) {
        return;
    }

    const link = event.target instanceof Element ? event.target.closest('a[data-leaving-href]') : null;
    if (link === null) {
        return;
    }

    event.preventDefault();
    window.open(link.dataset.leavingHref, '_blank', 'noopener');
}

/**
 * Routes every current and future `a[data-leaving-href]` inside root through its leaving page.
 *
 * @param {Document|HTMLElement} root
 * @returns {void}
 */
function registerLeavingLinks(root) {
    root.addEventListener('click', openLeavingPage);
    // Middle clicks fire auxclick, not click
    root.addEventListener('auxclick', openLeavingPage);
}

module.exports = {registerLeavingLinks};
