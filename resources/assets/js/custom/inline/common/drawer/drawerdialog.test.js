// ---------------------------------------------------------------------------
// `drawerdialog.js` is concatenated into a bundle in the browser and references
// jQuery and Bootstrap as bare globals; `bootstrap.Offcanvas` is stubbed, since
// jsdom has no Bootstrap.
// ---------------------------------------------------------------------------

const jQuery = require('jquery');

const {DrawerDialog} = require('./drawerdialog');

describe('DrawerDialog', () => {
    let previousJquery;
    let previousBootstrap;
    let offcanvas;
    let dialog;

    beforeEach(() => {
        previousJquery    = globalThis.$;
        previousBootstrap = globalThis.bootstrap;
        globalThis.$      = jQuery;

        offcanvas = {show: vi.fn(), hide: vi.fn()};
        globalThis.bootstrap = {Offcanvas: {getOrCreateInstance: vi.fn(() => offcanvas)}};

        document.body.innerHTML = `
            <button class="open_drawer">Open</button>
            <div id="drawer">
                <button id="drawer_confirm"></button>
                <div id="drawer_status"></div>
            </div>`;

        dialog = new DrawerDialog({
            drawerSelector:        '#drawer',
            openButtonSelector:    '.open_drawer',
            confirmButtonSelector: '#drawer_confirm',
            statusSelector:        '#drawer_status',
        });
        dialog.activate();
    });

    afterEach(() => {
        globalThis.$         = previousJquery;
        globalThis.bootstrap = previousBootstrap;
        document.body.innerHTML = '';
        jQuery(document).off('click');
    });

    it('openButton_givenAClick_showsTheDrawer', () => {
        // Act
        document.querySelector('.open_drawer').click();

        // Assert
        expect(offcanvas.show).toHaveBeenCalledTimes(1);
    });

    it('close_givenAnOpenDrawer_hidesIt', () => {
        // Act
        dialog.close();

        // Assert
        expect(offcanvas.hide).toHaveBeenCalledTimes(1);
    });

    it('onFirstShow_givenTheDrawerIsShownTwice_callsBackOnce', () => {
        // Arrange
        const callback = vi.fn();
        dialog.onFirstShow(callback);

        // Act
        jQuery('#drawer').trigger('show.bs.offcanvas');
        jQuery('#drawer').trigger('show.bs.offcanvas');

        // Assert
        expect(callback).toHaveBeenCalledTimes(1);
        expect(dialog.hasBeenShown()).toBe(true);
    });

    it('onShow_givenTheDrawerIsShownThreeTimes_callsBackForEveryShowAfterTheFirst', () => {
        // Arrange
        const callback = vi.fn();
        dialog.onShow(callback);

        // Act
        jQuery('#drawer').trigger('show.bs.offcanvas');
        jQuery('#drawer').trigger('show.bs.offcanvas');
        jQuery('#drawer').trigger('show.bs.offcanvas');

        // Assert
        expect(callback).toHaveBeenCalledTimes(2);
    });

    it('onConfirm_givenTheConfirmButtonIsClicked_callsBackWithoutClosing', () => {
        // Arrange
        const callback = vi.fn();
        dialog.onConfirm(callback);

        // Act
        document.querySelector('#drawer_confirm').click();

        // Assert
        expect(callback).toHaveBeenCalledTimes(1);
        expect(offcanvas.hide).not.toHaveBeenCalled();
    });

    it('setConfirmButton_givenDisabled_setsTheTextAndDisablesTheButton', () => {
        // Act
        dialog.setConfirmButton('Add 2 routes', false);

        // Assert
        expect(document.querySelector('#drawer_confirm').textContent).toBe('Add 2 routes');
        expect(document.querySelector('#drawer_confirm').disabled).toBe(true);
    });

    it('trigger_givenAnEvent_firesItOnTheDrawerElement', () => {
        // Arrange
        const listener = vi.fn();
        jQuery('#drawer').on('drawer:done', (event, result) => listener(result));

        // Act
        dialog.trigger('drawer:done', [{ok: true}]);

        // Assert
        expect(listener).toHaveBeenCalledWith({ok: true});
    });
});
