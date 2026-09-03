// ---------------------------------------------------------------------------
// guardedAjaxClick() exists so a double-click (or double-tap) on a click-driven $.ajax() trigger
// does not fire the request twice before the first response lands (#4455) - the pattern #4449
// already applied to the login/register form's submit handler, generalised for buttons that are
// not inside a <form>.
//
// Uses a real jQuery (unlike the minimal `$` stub in test/setup.js) since the guard is keyed on
// jQuery's per-element data store and toggles the `disabled` property.
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../inlinecode');
globalThis.InlineCode = InlineCode;

const {guardedAjaxClick} = require('./app');

describe('guardedAjaxClick', () => {
    let $button;
    let ajaxCalls;

    beforeEach(() => {
        $button = $('<button></button>').appendTo(document.body);
        ajaxCalls = [];

        $.ajax = (settings) => {
            ajaxCalls.push(settings);
            return {};
        };
    });

    afterEach(() => {
        $button.remove();
    });

    test('guardedAjaxClick_givenNoRequestInFlight_firesTheRequestAndDisablesTheTrigger', () => {
        // Act
        guardedAjaxClick($button, {type: 'POST', url: '/ajax/tag/1/all'});

        // Assert
        expect(ajaxCalls).toHaveLength(1);
        expect($button.prop('disabled')).toBe(true);
    });

    test('guardedAjaxClick_givenRequestAlreadyInFlightForThatTrigger_doesNotFireASecondRequest', () => {
        // Arrange
        guardedAjaxClick($button, {type: 'POST', url: '/ajax/tag/1/all'});

        // Act
        const result = guardedAjaxClick($button, {type: 'POST', url: '/ajax/tag/1/all'});

        // Assert
        expect(ajaxCalls).toHaveLength(1);
        expect(result).toBeUndefined();
    });

    test('guardedAjaxClick_givenRequestCompletes_reEnablesTheTriggerAndCallsTheOriginalComplete', () => {
        // Arrange
        let originalCompleteCalled = false;

        guardedAjaxClick($button, {
            type: 'POST',
            url: '/ajax/tag/1/all',
            complete: () => {
                originalCompleteCalled = true;
            },
        });

        // Act
        ajaxCalls[0].complete({status: 200}, 'success');

        // Assert
        expect($button.prop('disabled')).toBe(false);
        expect(originalCompleteCalled).toBe(true);
    });

    test('guardedAjaxClick_givenRequestCompletes_allowsANewRequestForTheSameTrigger', () => {
        // Arrange
        guardedAjaxClick($button, {type: 'POST', url: '/ajax/tag/1/all'});
        ajaxCalls[0].complete({status: 200}, 'success');

        // Act
        guardedAjaxClick($button, {type: 'POST', url: '/ajax/tag/1/all'});

        // Assert
        expect(ajaxCalls).toHaveLength(2);
    });

    test('guardedAjaxClick_givenCompleteIsAnArray_callsEveryCallbackInIt', () => {
        // Arrange - jQuery's own $.ajax() accepts `complete` as a function or an array of them
        const calls = [];

        guardedAjaxClick($button, {
            type: 'POST',
            url: '/ajax/tag/1/all',
            complete: [
                () => calls.push('first'),
                () => calls.push('second'),
            ],
        });

        // Act
        ajaxCalls[0].complete({status: 200}, 'success');

        // Assert
        expect(calls).toEqual(['first', 'second']);
    });

    test('guardedAjaxClick_givenCompleteReadsThis_seesTheSameContextJqueryWouldHaveBoundIt', () => {
        // Arrange - jQuery invokes `complete` with `this` bound to `ajaxSettings.context` (or the
        // settings object itself), which callers may rely on instead of a closure
        let seenContext = null;

        guardedAjaxClick($button, {
            type: 'POST',
            url: '/ajax/tag/1/all',
            complete: function () {
                seenContext = this;
            },
        });

        // Act - simulate jQuery invoking `complete` bound to the context it was given
        const context = {some: 'context'};
        ajaxCalls[0].complete.call(context, {status: 200}, 'success');

        // Assert
        expect(seenContext).toBe(context);
    });

    test('guardedAjaxClick_givenTwoDifferentTriggers_bothFireIndependently', () => {
        // Arrange
        const $otherButton = $('<button></button>').appendTo(document.body);

        // Act
        guardedAjaxClick($button, {type: 'POST', url: '/ajax/tag/1/all'});
        guardedAjaxClick($otherButton, {type: 'POST', url: '/ajax/tag/2/all'});

        // Assert
        expect(ajaxCalls).toHaveLength(2);

        $otherButton.remove();
    });
});
