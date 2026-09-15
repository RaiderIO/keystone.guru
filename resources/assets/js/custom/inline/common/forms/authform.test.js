// ---------------------------------------------------------------------------
// `authform.js` is concatenated into a bundle in the browser and references its
// collaborators as bare globals, so `InlineCode` must be on `globalThis` before
// the class body is evaluated (same pattern as inlinemanager.test.js).
//
// Instances are constructed directly rather than through `activate()`: the shared
// `$` stub in test/setup.js is not a real jQuery. Tests that need `_submit()` to
// run install their own `$` stub for the duration of the call.
// ---------------------------------------------------------------------------

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonFormsAuthform} = require('./authform');

/**
 * @param {Object} options
 * @returns {CommonFormsAuthform}
 */
function makeAuthform(options) {
    return new CommonFormsAuthform('test-id', 'common/forms/authform', options);
}

describe('CommonFormsAuthform._navigate', () => {
    let assignedHref;
    let reloadCount;

    beforeEach(() => {
        // jsdom refuses a real navigation, so stand in for the two things `_navigate` can do.
        assignedHref = null;
        reloadCount  = 0;

        delete window.location;
        window.location = {
            reload: () => {
                reloadCount++;
            },
            set href(url) {
                assignedHref = url;
            },
            get href() {
                return assignedHref;
            },
        };
    });

    it('_navigate_givenSuccessUrl_navigatesToThatUrl', () => {
        // Arrange
        const authform = makeAuthform({formSelector: '#modal-register_form', successUrl: 'https://keystone.guru'});

        // Act
        authform._navigate(authform.options.successUrl);

        // Assert
        expect(assignedHref).toBe('https://keystone.guru');
        expect(reloadCount).toBe(0);
    });

    it('_navigate_givenNullSuccessUrl_reloadsCurrentPage', () => {
        // Arrange
        const authform = makeAuthform({formSelector: '#modal-register_form', successUrl: null});

        // Act
        authform._navigate(authform.options.successUrl);

        // Assert
        expect(reloadCount).toBe(1);
        expect(assignedHref).toBeNull();
    });

    it('_navigate_givenMissingSuccessUrl_reloadsCurrentPage', () => {
        // Arrange
        const authform = makeAuthform({formSelector: '#modal-register_form'});

        // Act
        authform._navigate(authform.options.successUrl);

        // Assert
        expect(reloadCount).toBe(1);
        expect(assignedHref).toBeNull();
    });

    it('_navigate_givenEmptySuccessUrl_reloadsCurrentPage', () => {
        // Arrange
        const authform = makeAuthform({formSelector: '#modal-register_form', successUrl: ''});

        // Act
        authform._navigate(authform.options.successUrl);

        // Assert
        expect(reloadCount).toBe(1);
        expect(assignedHref).toBeNull();
    });
});

describe('CommonFormsAuthform._submit', () => {
    /**
     * Runs `_submit()` against a jQuery stub whose `$.ajax` immediately invokes the success
     * callback, and reports what `_navigate()` was called with. This proves the success callback
     * is bound to the instance and hands its configured `successUrl` through.
     *
     * @param {Object} options
     * @returns {Array<string|null|undefined>}
     */
    function submitWithSuccessfulAjax(options) {
        const authform  = makeAuthform(options);
        const navigated = [];
        vi.spyOn(authform, '_navigate').mockImplementation((url) => {
            navigated.push(url);
        });

        // A chainable no-op stand-in for every jQuery collection `_submit()` touches.
        const $collection = {
            length:      0,
            val:         () => $collection,
            attr:        () => '/register',
            serialize:   () => '',
            find:        () => $collection,
            removeClass: () => $collection,
            removeAttr:  () => $collection,
            remove:      () => $collection,
            first:       () => $collection,
            trigger:     () => $collection,
        };

        const originalJQuery = globalThis.$;
        const $stub          = () => $collection;
        $stub.fn             = originalJQuery.fn;
        $stub.ajax           = (settings) => {
            settings.success();
            settings.complete();
        };

        globalThis.$ = $stub;

        try {
            authform._submit({preventDefault: () => {}});
        } finally {
            globalThis.$ = originalJQuery;
        }

        return navigated;
    }

    it('_submit_givenSuccessAndSuccessUrl_navigatesToTheSuccessUrl', () => {
        // Arrange & Act
        const navigated = submitWithSuccessfulAjax({formSelector: '#modal-register_form', successUrl: '/'});

        // Assert - registering from the modal on /register must land on the home page
        expect(navigated).toEqual(['/']);
    });

    it('_submit_givenSuccessWithoutSuccessUrl_reloadsTheCurrentPage', () => {
        // Arrange & Act
        const navigated = submitWithSuccessfulAjax({formSelector: '#modal-register_form', successUrl: null});

        // Assert - `_navigate` falls back to a reload for a null url (covered above)
        expect(navigated).toEqual([null]);
    });

    /**
     * A double-click fires two 'submit' events before the first request's response arrives. Uses
     * a `$.ajax` stub that never calls `success`/`error`/`complete`, so the first request stays
     * "in flight" for the duration of the test.
     *
     * @returns {number} how many times `$.ajax` was invoked
     */
    function submitTwiceWithoutCompletingFirstAjax() {
        const authform = makeAuthform({formSelector: '#modal-register_form', successUrl: null});

        const $collection = {
            length:      0,
            attr:        () => '/register',
            serialize:   () => '',
            find:        () => $collection,
            removeClass: () => $collection,
            removeAttr:  () => $collection,
            remove:      () => $collection,
            first:       () => $collection,
            trigger:     () => $collection,
        };

        let ajaxCallCount = 0;
        const originalJQuery = globalThis.$;
        const $stub          = () => $collection;
        $stub.fn              = originalJQuery.fn;
        $stub.ajax             = () => {
            ajaxCallCount++;
        };

        globalThis.$ = $stub;

        try {
            authform._submit({preventDefault: () => {}});
            authform._submit({preventDefault: () => {}});
        } finally {
            globalThis.$ = originalJQuery;
        }

        return ajaxCallCount;
    }

    it('_submit_givenSecondSubmitWhileFirstStillInFlight_ignoresTheSecondSubmit', () => {
        // Arrange & Act
        const ajaxCallCount = submitTwiceWithoutCompletingFirstAjax();

        // Assert
        expect(ajaxCallCount).toBe(1);
    });

    it('_submit_givenSubmitAfterAPreviousRequestCompleted_sendsTheRequest', () => {
        // Arrange - the first submit runs to completion (`complete` fires), so the in-flight flag
        // must have been cleared before the second submit on the same instance
        const authform = makeAuthform({formSelector: '#modal-register_form', successUrl: null});

        const $collection = {
            length:      0,
            attr:        () => '/register',
            serialize:   () => '',
            find:        () => $collection,
            removeClass: () => $collection,
            removeAttr:  () => $collection,
            remove:      () => $collection,
            first:       () => $collection,
            trigger:     () => $collection,
        };

        let ajaxCallCount = 0;
        const originalJQuery = globalThis.$;
        const $stub          = () => $collection;
        $stub.fn              = originalJQuery.fn;
        $stub.ajax             = (settings) => {
            ajaxCallCount++;
            settings.success();
            settings.complete();
        };

        globalThis.$ = $stub;

        try {
            // Act
            authform._submit({preventDefault: () => {}});
            authform._submit({preventDefault: () => {}});
        } finally {
            globalThis.$ = originalJQuery;
        }

        // Assert
        expect(ajaxCallCount).toBe(2);
    });
});

describe('CommonFormsAuthform._renderErrors', () => {
    let previousJquery;
    let previousShowErrorNotification;

    beforeEach(() => {
        // `_renderErrors()` only walks and edits the DOM, so run it against a real jQuery
        previousJquery                     = globalThis.$;
        previousShowErrorNotification      = globalThis.showErrorNotification;
        globalThis.$                       = require('jquery');
        globalThis.showErrorNotification   = () => {};

        document.body.innerHTML = `
            <form id="modal-register_form">
                <div class="mb-3">
                    <input id="modal-register_email" name="email" class="form-control">
                </div>
                <div class="mb-3">
                    <div class="input-group">
                        <input id="modal-register_password" name="password" type="password" class="form-control">
                        <button id="modal-register_password_reveal" type="button">show</button>
                    </div>
                </div>
            </form>`;
    });

    afterEach(() => {
        globalThis.$                     = previousJquery;
        globalThis.showErrorNotification = previousShowErrorNotification;
        document.body.innerHTML          = '';
    });

    it('_renderErrors_givenErrorForInputInsideInputGroup_rendersTheMessageAfterTheGroup', () => {
        // Arrange
        const authform = makeAuthform({formSelector: '#modal-register_form'});

        // Act
        authform._renderErrors(globalThis.$('#modal-register_form'), {password: ['Too short.']});

        // Assert - not wedged between the input and its reveal button
        const group    = document.querySelector('.input-group');
        const feedback = group.nextElementSibling;
        expect(group.querySelector('.invalid-feedback')).toBeNull();
        expect(feedback.classList.contains('invalid-feedback')).toBe(true);
        expect(feedback.classList.contains('d-block')).toBe(true);
        expect(feedback.getAttribute('role')).toBe('alert');
        expect(feedback.textContent).toBe('Too short.');
        expect(document.querySelector('#modal-register_password').getAttribute('aria-invalid')).toBe('true');
    });

    it('_renderErrors_givenErrorForPlainInput_rendersTheMessageRightAfterTheInput', () => {
        // Arrange
        const authform = makeAuthform({formSelector: '#modal-register_form'});

        // Act
        authform._renderErrors(globalThis.$('#modal-register_form'), {email: ['Invalid e-mail.']});

        // Assert
        const feedback = document.querySelector('#modal-register_email').nextElementSibling;
        expect(feedback.classList.contains('invalid-feedback')).toBe(true);
        expect(feedback.textContent).toBe('Invalid e-mail.');
    });
});
