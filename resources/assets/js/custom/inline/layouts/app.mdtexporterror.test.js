// ---------------------------------------------------------------------------
// The MDT export url carries a signature minted when the page was rendered (#4538), so a page left
// open past the expiry window gets a 403 back. The generic 403 message ('you are not authorized')
// would send the user looking for a permission problem that does not exist - the fix is to reload.
//
// Notifications are asserted through the global Noty stub, which is what _showNotification() drives.
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../inlinecode');
globalThis.InlineCode = InlineCode;

const notifications = [];
globalThis.Noty = class {
    constructor(options) {
        notifications.push(options);
    }

    show() {
    }
};

const {mdtExportAjaxErrorFn} = require('./app');

describe('mdtExportAjaxErrorFn', () => {
    beforeEach(() => {
        notifications.length = 0;
    });

    test('mdtExportAjaxErrorFn_givenExpiredSignature_tellsTheUserToReload', () => {
        // Arrange - what ValidateSignature returns once the minted url is past its expiry
        const xhr = {status: 403};

        // Act
        mdtExportAjaxErrorFn(xhr, 'error', 'Forbidden');

        // Assert
        expect(notifications).toHaveLength(1);
        expect(notifications[0].type).toBe('error');
        expect(notifications[0].text).toContain('js.mdt_export_url_expired');
    });

    test('mdtExportAjaxErrorFn_givenUnrelatedError_fallsBackToTheDefaultHandler', () => {
        // Arrange - a dungeon MDT cannot export returns 400, which is not a signature problem
        const xhr = {status: 400, responseJSON: {message: 'Failed to generate MDT string'}};

        // Act
        mdtExportAjaxErrorFn(xhr, 'error', 'Bad Request');

        // Assert
        expect(notifications).toHaveLength(1);
        expect(notifications[0].text).toContain('400: Failed to generate MDT string');
    });

    test('mdtExportAjaxErrorFn_givenAbortedRequest_showsNoNotification', () => {
        // Arrange - navigating away mid-request must not scold the user on the way out
        const xhr = {status: 0};

        // Act
        mdtExportAjaxErrorFn(xhr, 'abort', 'abort');

        // Assert
        expect(notifications).toHaveLength(0);
    });
});
