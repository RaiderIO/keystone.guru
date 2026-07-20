// ---------------------------------------------------------------------------
// Covers $.fn.passwordStrength (#3597), the self-owned replacement for the
// removed `password-strength-meter` plugin. The widget renders a Bootstrap
// progress bar + text tip below a password <input>, scoring the typed value
// with zxcvbn-ts (debounced) on every 'input'/'change' event.
// ---------------------------------------------------------------------------

const $ = require('jquery');
require('./password-strength');

const OPTIONS = {
    shortPass: 'too short',
    badPass: 'weak',
    goodPass: 'medium',
    strongPass: 'strong',
    minimumLength: 8,
};

// Comfortably past the widget's 200ms debounce on the actual zxcvbn scoring call.
const DEBOUNCE_WAIT_MS = 300;

/**
 * @returns {HTMLInputElement}
 */
function createPasswordInput() {
    document.body.innerHTML = '<div><input type="password" id="register_password" /></div>';

    return document.querySelector('#register_password');
}

/**
 * @param {HTMLInputElement} input
 * @param {string} value
 */
async function typePassword(input, value) {
    input.value = value;
    $(input).trigger('input');
    await new Promise((resolve) => setTimeout(resolve, DEBOUNCE_WAIT_MS));
}

describe('$.fn.passwordStrength (#3597)', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('passwordStrength_givenInput_rendersBootstrapProgressBarInClosestWrappingDiv', () => {
        const input = createPasswordInput();

        $(input).passwordStrength(OPTIONS);

        const $bar = $('.password-strength .progress > .progress-bar');
        expect(input.closest('div').querySelector('.password-strength')).not.toBeNull();
        expect($bar.length).toBe(1);
        expect($bar.attr('role')).toBe('progressbar');
        expect(document.querySelector('.password-strength__text').innerHTML).toBe('&nbsp;');
    });

    test('passwordStrength_givenEmptyInput_showsBlankTextAndNoWidth', async () => {
        const input = createPasswordInput();
        $(input).passwordStrength(OPTIONS);

        await typePassword(input, 'something');
        await typePassword(input, '');

        expect(document.querySelector('.password-strength__text').innerHTML).toBe('&nbsp;');
        expect(document.querySelector('.progress-bar').style.width).toBe('0%');
    });

    test('passwordStrength_givenPasswordShorterThanMinimum_showsShortPassTipImmediately', async () => {
        const input = createPasswordInput();
        $(input).passwordStrength(OPTIONS);

        // No debounce wait: the length check short-circuits before the debounced zxcvbn call.
        input.value = 'abc123';
        $(input).trigger('input');

        const $bar = $('.progress-bar');
        expect(document.querySelector('.password-strength__text').textContent).toBe(OPTIONS.shortPass);
        expect($bar.hasClass('bg-danger')).toBe(true);
    });

    test('passwordStrength_givenCommonWord_showsWeakTip', async () => {
        const input = createPasswordInput();
        $(input).passwordStrength(OPTIONS);

        await typePassword(input, 'password');

        const $bar = $('.progress-bar');
        expect(document.querySelector('.password-strength__text').textContent).toBe(OPTIONS.badPass);
        expect($bar.hasClass('bg-danger')).toBe(true);
    });

    test('passwordStrength_givenLongRandomPassword_showsStrongTip', async () => {
        const input = createPasswordInput();
        $(input).passwordStrength(OPTIONS);

        await typePassword(input, 'Tr7$kQm2!vLp9#Zx');

        const $bar = $('.progress-bar');
        expect(document.querySelector('.password-strength__text').textContent).toBe(OPTIONS.strongPass);
        expect($bar.hasClass('bg-success')).toBe(true);
        expect($bar[0].style.width).toBe('100%');
        expect($bar.attr('aria-valuenow')).toBe('100');
    });

    test('passwordStrength_givenRepeatInit_doesNotDuplicateWidget', () => {
        const input = createPasswordInput();

        $(input).passwordStrength(OPTIONS);
        $(input).passwordStrength(OPTIONS);

        expect(document.querySelectorAll('.password-strength').length).toBe(1);
    });

    test('passwordStrength_givenNonInputElement_doesNotThrowAndRendersNothing', () => {
        document.body.innerHTML = '<div><select id="not_a_password"></select></div>';

        expect(() => $('#not_a_password').passwordStrength(OPTIONS)).not.toThrow();
        expect(document.querySelector('.password-strength')).toBeNull();
    });

    test('passwordStrength_givenNoMatchingInput_doesNotThrowAndRendersNothing', () => {
        document.body.innerHTML = '';

        expect(() => $('#register_password').passwordStrength(OPTIONS)).not.toThrow();
        expect(document.querySelector('.password-strength')).toBeNull();
    });
});
