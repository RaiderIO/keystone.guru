// ---------------------------------------------------------------------------
// showConfirmYesCancel() lets a destructive confirm name its action on the buttons instead of Yes/Cancel. The
// buttons are asserted through the global Noty stub, which is what _showConfirm() drives.
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../inlinecode');
globalThis.InlineCode = InlineCode;

const confirms = [];
globalThis.Noty = class {
    constructor(options) {
        confirms.push(options);
    }

    show() {
    }

    static button(text, classNames, callback, attributes = {}) {
        return {text, classNames, callback, attributes};
    }
};

const {showConfirmYesCancel} = require('./app');

describe('showConfirmYesCancel', () => {
    let previousLang;

    beforeEach(() => {
        confirms.length = 0;
        previousLang    = globalThis.lang;
        globalThis.lang = {get: (key) => key};
    });

    afterEach(() => {
        globalThis.lang = previousLang;
    });

    test('showConfirmYesCancel_givenNoLabels_offersYesAndCancel', () => {
        // Arrange
        const yes = vi.fn();

        // Act
        showConfirmYesCancel('Sure?', yes);

        // Assert
        expect(confirms).toHaveLength(1);
        expect(confirms[0].buttons.map(button => button.text)).toEqual(['js.yes_label', 'js.cancel_label']);
        expect(confirms[0].buttons.map(button => button.classNames)).toEqual(['btn btn-success me-1', 'btn btn-danger']);
    });

    test('showConfirmYesCancel_givenLabelsAndClasses_namesTheActionOnItsButtonsAndKeepsThemOutOfNoty', () => {
        // Arrange
        const yes = vi.fn();

        // Act
        showConfirmYesCancel('Delete it?', yes, null, {
            yesLabel:    'Delete collection',
            yesClass:    'btn btn-danger me-1',
            cancelLabel: 'Keep it',
            cancelClass: 'btn btn-secondary',
            timeout:     false,
        });

        // Assert
        expect(confirms[0].buttons.map(button => button.text)).toEqual(['Delete collection', 'Keep it']);
        expect(confirms[0].buttons.map(button => button.classNames)).toEqual(['btn btn-danger me-1', 'btn btn-secondary']);
        expect(confirms[0].timeout).toBe(false);
        expect(confirms[0]).not.toHaveProperty('yesLabel');
        expect(confirms[0]).not.toHaveProperty('cancelClass');
    });

    test('showConfirmYesCancel_givenTheActionConfirmed_runsItsCallback', () => {
        // Arrange
        const yes = vi.fn();
        showConfirmYesCancel('Delete it?', yes, null, {yesLabel: 'Delete collection'});
        const notification = {close: vi.fn()};

        // Act
        confirms[0].buttons[0].callback(notification);

        // Assert
        expect(yes).toHaveBeenCalledTimes(1);
        expect(notification.close).toHaveBeenCalledTimes(1);
    });
});
