// ---------------------------------------------------------------------------
// defaultAjaxErrorFn is installed globally through $.ajaxSetup, so it also sees the requests we abort
// ourselves whenever a search is superseded (SearchHandler.search). Those aborts never got an HTTP response,
// so they used to surface as a '0: An error occurred while performing your request' notification the user
// could do nothing about (#3744).
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

const {defaultAjaxErrorFn} = require('./app');

describe('defaultAjaxErrorFn', () => {
    beforeEach(() => {
        notifications.length = 0;
    });

    test('defaultAjaxErrorFn_givenAbortedRequest_showsNoNotification', () => {
        // Arrange - an aborted request never received a response, hence status 0
        const xhr = {status: 0};

        // Act
        defaultAjaxErrorFn(xhr, 'abort', 'abort');

        // Assert
        expect(notifications).toHaveLength(0);
    });

    test('defaultAjaxErrorFn_givenServerError_showsNotificationWithStatusAndMessage', () => {
        // Arrange
        const xhr = {status: 500, responseJSON: {message: 'Invalid response from Raider.IO API'}};

        // Act
        defaultAjaxErrorFn(xhr, 'error', 'Internal Server Error');

        // Assert
        expect(notifications).toHaveLength(1);
        expect(notifications[0].type).toBe('error');
        expect(notifications[0].text).toContain('500: Invalid response from Raider.IO API');
    });

    test('defaultAjaxErrorFn_givenValidationErrors_showsTheValidationMessages', () => {
        // Arrange
        const xhr = {status: 422, responseJSON: {errors: {dungeonId: 'The dungeon id field is required.'}}};

        // Act
        defaultAjaxErrorFn(xhr, 'error', 'Unprocessable Content');

        // Assert
        expect(notifications).toHaveLength(1);
        expect(notifications[0].text).toContain('422: The dungeon id field is required.');
    });
});
