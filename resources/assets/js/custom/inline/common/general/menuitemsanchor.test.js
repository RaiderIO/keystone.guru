// ---------------------------------------------------------------------------
// CommonGeneralMenuitemsanchor is a global-script style class: it extends the
// bare global `InlineCode` and uses `$` (jQuery) and `bootstrap` at runtime. To
// exercise activate() in isolation:
//
//   1. Require inlinecode.js and expose InlineCode on globalThis so the subclass
//      can reference it as a bare global (as it does in the browser bundle).
//   2. Use REAL jQuery (the shared test/setup.js stub is a no-op object that
//      would not actually bind the click handler under test).
//   3. Stub `bootstrap.Tab` so the on-load anchor block does not throw.
// ---------------------------------------------------------------------------

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.$ = require('jquery');
globalThis.bootstrap = {
    Tab: {getOrCreateInstance: () => ({show() {}})},
};

const {CommonGeneralMenuitemsanchor} = require('./menuitemsanchor');

function buildTabbedDom() {
    document.body.innerHTML = `
        <ul class="nav flex-column nav-pills">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#profile" role="tab">Profile</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#account" role="tab">Account</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="profile"></div>
            <div class="tab-pane" id="account"></div>
        </div>
    `;
}

describe('CommonGeneralMenuitemsanchor.activate', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        // Reset the fragment so tests do not leak state into each other.
        history.replaceState(undefined, undefined, ' ');
    });

    it('activate_givenNavLinkClicked_updatesHashWithoutScrolling', () => {
        // Arrange
        buildTabbedDom();
        const replaceStateSpy = vi.spyOn(history, 'replaceState');
        const code = new CommonGeneralMenuitemsanchor('id', 'common/general/menuitemsanchor', {});
        code.activate();

        // Act
        document.querySelector('a[href="#account"]').click();

        // Assert
        expect(replaceStateSpy).toHaveBeenCalledWith(undefined, undefined, '#account');
        expect(window.location.hash).toBe('#account');
    });

    it('activate_givenNavLinkClicked_doesNotAssignLocationHashDirectly', () => {
        // Arrange: assigning window.location.hash directly is what caused the
        // browser to scroll to the anchored pane; assert the non-scrolling
        // history.replaceState path is used instead.
        buildTabbedDom();
        const replaceStateSpy = vi.spyOn(history, 'replaceState');
        const code = new CommonGeneralMenuitemsanchor('id', 'common/general/menuitemsanchor', {});
        code.activate();

        // Act
        document.querySelector('a[href="#profile"]').click();

        // Assert
        expect(replaceStateSpy).toHaveBeenCalledTimes(1);
        expect(replaceStateSpy).toHaveBeenCalledWith(undefined, undefined, '#profile');
    });
});
