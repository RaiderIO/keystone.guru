// ---------------------------------------------------------------------------
// `passwordinput.js` is concatenated into a bundle in the browser and references
// its collaborators as bare globals, so `InlineCode` must be on `globalThis`
// before the class body is evaluated (same pattern as authform.test.js).
//
// The toggle only flips attributes and classes, so these tests run it against a
// real jQuery over jsdom markup mirroring common/forms/passwordinput.blade.php,
// installed as the global `$` for the duration of each test.
// ---------------------------------------------------------------------------

const jQuery = require('jquery');

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonFormsPasswordinput} = require('./passwordinput');

const OPTIONS = {
    inputSelector:  '#register_password',
    toggleSelector: '#register_password_reveal',
};

describe('CommonFormsPasswordinput', () => {
    let previousJquery;

    beforeEach(() => {
        previousJquery = globalThis.$;
        globalThis.$   = jQuery;

        document.body.innerHTML = `
            <div class="input-group">
                <input id="register_password" type="password" class="form-control" name="password">
                <button id="register_password_reveal" type="button" class="btn btn-password-reveal"
                        aria-controls="register_password" aria-pressed="false" aria-label="Show password">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                </button>
            </div>`;
    });

    afterEach(() => {
        globalThis.$            = previousJquery;
        document.body.innerHTML = '';
    });

    /**
     * @returns {{type: string, pressed: string, icon: string, label: string}}
     */
    function state() {
        const toggle = document.querySelector('#register_password_reveal');

        return {
            type:    document.querySelector('#register_password').type,
            pressed: toggle.getAttribute('aria-pressed'),
            icon:    toggle.querySelector('i').className,
            label:   toggle.getAttribute('aria-label'),
        };
    }

    it('_toggle_givenHiddenPassword_revealsItAndReportsPressed', () => {
        // Arrange
        const passwordInput = new CommonFormsPasswordinput('test-id', 'common/forms/passwordinput', OPTIONS);

        // Act
        passwordInput._toggle();

        // Assert
        expect(state()).toEqual({type: 'text', pressed: 'true', icon: 'fas fa-eye-slash', label: 'Show password'});
    });

    it('_toggle_givenRevealedPassword_hidesItAgain', () => {
        // Arrange
        const passwordInput = new CommonFormsPasswordinput('test-id', 'common/forms/passwordinput', OPTIONS);
        passwordInput._toggle();

        // Act
        passwordInput._toggle();

        // Assert - the accessible name stays fixed; only aria-pressed carries the state
        expect(state()).toEqual({type: 'password', pressed: 'false', icon: 'fas fa-eye', label: 'Show password'});
    });

    it('activate_givenClickOnToggle_togglesOnceNoMatterHowOftenActivated', () => {
        // Arrange - activating twice must not stack a second handler that undoes the first
        const passwordInput = new CommonFormsPasswordinput('test-id', 'common/forms/passwordinput', OPTIONS);
        passwordInput.activate();
        passwordInput.activate();

        // Act
        document.querySelector('#register_password_reveal').click();

        // Assert
        expect(state().type).toBe('text');
    });
});
