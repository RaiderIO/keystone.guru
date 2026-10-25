const {registerLeavingLinks} = require('./leaving-link');

const DESTINATION = 'https://example.com/me';
const LEAVING_PAGE = 'https://keystone.guru/user/someone/link/1';

/**
 * @param {string} attributes
 * @returns {{root: HTMLElement, icon: HTMLElement}}
 */
function createLink(attributes) {
    const root = document.createElement('div');
    root.innerHTML = `<a href="${DESTINATION}" ${attributes} target="_blank"><i></i></a>`;
    document.body.appendChild(root);
    registerLeavingLinks(root);

    return {root, icon: root.querySelector('i')};
}

/**
 * @param {HTMLElement} target
 * @param {string} type
 * @param {number} button
 * @returns {MouseEvent}
 */
function dispatchMouseEvent(target, type, button) {
    const event = new MouseEvent(type, {bubbles: true, cancelable: true, button});
    target.dispatchEvent(event);

    return event;
}

describe('registerLeavingLinks', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    test.each([
        ['click', 0],
        ['auxclick', 1],
    ])('opens the leaving page instead of the destination on %s', (type, button) => {
        // Arrange
        const open = vi.spyOn(window, 'open').mockReturnValue(null);
        const {icon} = createLink(`data-leaving-href="${LEAVING_PAGE}"`);

        // Act
        const event = dispatchMouseEvent(icon, type, button);

        // Assert
        expect(event.defaultPrevented).toBe(true);
        expect(open).toHaveBeenCalledExactlyOnceWith(LEAVING_PAGE, '_blank', 'noopener');
    });

    test('leaves a right click alone so the context menu still offers the destination', () => {
        // Arrange
        const open = vi.spyOn(window, 'open').mockReturnValue(null);
        const {icon} = createLink(`data-leaving-href="${LEAVING_PAGE}"`);

        // Act
        const event = dispatchMouseEvent(icon, 'auxclick', 2);

        // Assert
        expect(event.defaultPrevented).toBe(false);
        expect(open).not.toHaveBeenCalled();
    });

    test('leaves a link without a leaving page alone', () => {
        // Arrange
        const open = vi.spyOn(window, 'open').mockReturnValue(null);
        const {icon} = createLink('');

        // Act
        const event = dispatchMouseEvent(icon, 'click', 0);

        // Assert
        expect(event.defaultPrevented).toBe(false);
        expect(open).not.toHaveBeenCalled();
    });
});
