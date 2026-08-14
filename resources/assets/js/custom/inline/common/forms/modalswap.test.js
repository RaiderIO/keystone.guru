// ---------------------------------------------------------------------------
// `modalswap.js` is concatenated into a bundle in the browser and references its
// collaborators as bare globals, so `InlineCode` must be on `globalThis` before
// the class body is evaluated (same pattern as authform.test.js).
//
// Instances are constructed directly rather than through `activate()`: the shared
// `$` stub in test/setup.js is not a real jQuery. `_swap()` and the deferred
// `hidden.bs.modal` handler both run inside `withStubs()`, which installs the
// `$`/`bootstrap`/`document.querySelector` stand-ins only for the duration of the
// call and restores them afterwards.
// ---------------------------------------------------------------------------

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonFormsModalswap} = require('./modalswap');

const OPTIONS = {
    linkSelector: '#modal-login_register_link',
    fromModal:    '#login_modal',
    toModal:      '#register_modal',
};

/**
 * @returns {{calls: Array<string>, swap: (function(): boolean), fireHidden: (function(): void)}}
 */
function makeSwapHarness() {
    const modalswap = new CommonFormsModalswap('test-id', 'common/forms/modalswap', OPTIONS);

    const calls       = [];
    let hiddenHandler = null;

    // The `fromModal` collection: records the one-shot `hidden.bs.modal` subscription.
    const $fromModal = {
        0:   '#login_modal element',
        one: (event, handler) => {
            calls.push(`one:${event}`);
            hiddenHandler = handler;

            return $fromModal;
        },
    };

    /**
     * @param {Function} callback
     * @returns {*}
     */
    function withStubs(callback) {
        const originalJQuery        = globalThis.$;
        const originalBootstrap     = globalThis.bootstrap;
        const originalQuerySelector = document.querySelector.bind(document);

        const $stub = () => $fromModal;
        $stub.fn    = originalJQuery.fn;

        globalThis.$         = $stub;
        globalThis.bootstrap = {
            Modal: {
                getOrCreateInstance: (element) => ({
                    show: () => calls.push(`show:${element}`),
                    hide: () => calls.push(`hide:${element}`),
                }),
            },
        };
        // `_swap` resolves the modal to open through `document.querySelector`
        document.querySelector = (selector) => selector;

        try {
            return callback();
        } finally {
            globalThis.$           = originalJQuery;
            globalThis.bootstrap   = originalBootstrap;
            document.querySelector = originalQuerySelector;
        }
    }

    return {
        calls,
        swap: (event = {}) => withStubs(() => {
            let prevented = false;

            modalswap._swap({
                preventDefault: () => {
                    prevented = true;
                },
                ...event,
            });

            return prevented;
        }),
        fireHidden: () => withStubs(() => hiddenHandler()),
    };
}

describe('CommonFormsModalswap._swap', () => {
    it('_swap_givenCrossLinkClick_hidesTheCurrentModalInsteadOfNavigating', () => {
        // Arrange
        const {calls, swap} = makeSwapHarness();

        // Act
        const prevented = swap();

        // Assert
        expect(prevented).toBe(true);
        expect(calls).toContain('hide:#login_modal element');
    });

    it('_swap_givenTheFirstModalIsStillHiding_doesNotShowTheSecondYet', () => {
        // Arrange - Bootstrap stacks backdrops if the second modal opens before the first is gone
        const {calls, swap} = makeSwapHarness();

        // Act
        swap();

        // Assert
        expect(calls).toEqual(['one:hidden.bs.modal', 'hide:#login_modal element']);
    });

    it.each([
        ['ctrlKey'],
        ['metaKey'],
        ['shiftKey'],
        ['altKey'],
    ])('_swap_givenAModifiedClickWith%s_leavesTheNavigationToTheBrowser', (modifier) => {
        // Arrange - the cross-link has a real href to the standalone page
        const {calls, swap} = makeSwapHarness();

        // Act
        const prevented = swap({[modifier]: true});

        // Assert - nothing swapped, and the default was not suppressed
        expect(prevented).toBe(false);
        expect(calls).toEqual([]);
    });

    it('_swap_givenAMiddleClick_leavesTheNavigationToTheBrowser', () => {
        // Arrange
        const {calls, swap} = makeSwapHarness();

        // Act
        const prevented = swap({which: 2});

        // Assert
        expect(prevented).toBe(false);
        expect(calls).toEqual([]);
    });

    it('_swap_givenTheFirstModalFinishedHiding_showsTheSecondModal', () => {
        // Arrange
        const {calls, swap, fireHidden} = makeSwapHarness();
        swap();

        // Act
        fireHidden();

        // Assert
        expect(calls).toEqual(['one:hidden.bs.modal', 'hide:#login_modal element', 'show:#register_modal']);
    });
});
