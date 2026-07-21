// ---------------------------------------------------------------------------
// $.fn.passwordStrength (#3597)
//
// Self-owned replacement for the unmaintained `password-strength-meter` npm
// plugin (last release 2020, last GitHub push 2022). Renders a Bootstrap
// progress bar + text tip below a password <input>.
//
//     $('#register_password').passwordStrength({
//         shortPass: '...', badPass: '...', goodPass: '...', strongPass: '...',
//         minimumLength: 8,
//     });
//
// Scoring is a small, dependency-free character-class/repetition heuristic -
// the same kind of check the old plugin did - rather than a dictionary-backed
// library (e.g. zxcvbn-ts): a real dictionary-aware scorer needs several MB of
// wordlists, which would roughly double the size of the app's single global JS
// bundle (every page loads it, not just this widget's 2-3 call sites) for a
// login-form nicety. This trades dictionary awareness for a near-zero bundle
// cost.
//
// The widget is appended to the input's closest wrapping <div> (mirrors the
// former plugin's default `closestSelector: 'div'`) and updates on every
// input change. Widget visuals live in resources/assets/css/lib/password-strength.css.
// ---------------------------------------------------------------------------

const $ = require('jquery');

const SYMBOL_PATTERN = /[^a-zA-Z0-9]/;

/**
 * Counts the characters left after collapsing runs where the string repeats itself with the
 * given period (period 1 catches "aaaa", period 2 catches "abab", period 3 "abcabc", ...): each
 * repeated block is dropped entirely rather than counted once, so a repetitive password scores
 * as if most of its length wasn't there.
 *
 * @param {string} password
 * @param {number} period
 * @returns {number}
 */
function nonRepeatingLength(password, period) {
    let count = 0;

    for (let i = 0; i < password.length; i++) {
        let repeating = true;
        let checked = 0;

        for (; checked < period && i + checked + period < password.length; checked++) {
            repeating = repeating && password[i + checked] === password[i + checked + period];
        }

        if (checked < period) {
            repeating = false;
        }

        if (repeating) {
            i += period - 1;
        } else {
            count++;
        }
    }

    return count;
}

/**
 * Scores a password from 0 (worthless) to 100 (excellent) based on length, character-class
 * variety, and short repeating patterns. Not dictionary-aware: "P@ssw0rd" scores 86 (strong)
 * despite being a well-known example of a guessable password - same tradeoff the old plugin made.
 *
 * @param {string} password
 * @returns {number}
 */
function scorePassword(password) {
    let score = 4 * password.length;

    for (let period = 1; period <= 4; period++) {
        score += nonRepeatingLength(password, period) - password.length;
    }

    const hasLower = /[a-z]/.test(password);
    const hasUpper = /[A-Z]/.test(password);
    const hasDigit = /[0-9]/.test(password);
    const hasSymbol = SYMBOL_PATTERN.test(password);
    const digitCount = (password.match(/[0-9]/g) || []).length;
    const symbolCount = (password.match(/[^a-zA-Z0-9]/g) || []).length;

    if (digitCount >= 3) {
        score += 5;
    }
    if (symbolCount >= 2) {
        score += 5;
    }
    if (hasLower && hasUpper) {
        score += 10;
    }
    if ((hasLower || hasUpper) && hasDigit) {
        score += 15;
    }
    if (hasSymbol && hasDigit) {
        score += 15;
    }
    if (hasSymbol && (hasLower || hasUpper)) {
        score += 15;
    }
    if (/^\w+$/.test(password)) {
        // Only letters/digits/underscore and no symbol at all - the easiest class to brute-force.
        score -= 10;
    }

    return Math.max(0, Math.min(100, score));
}

/**
 * Turns each matched password <input> into a strength meter.
 *
 * @param {{shortPass?: string, badPass?: string, goodPass?: string, strongPass?: string, minimumLength?: number}} [options]
 * @returns {jQuery}
 */
$.fn.passwordStrength = function (options) {
    const settings = $.extend({
        shortPass: 'The password is too short',
        badPass: 'Weak; try combining letters & numbers',
        goodPass: 'Medium; try using special characters',
        strongPass: 'Strong password',
        minimumLength: 8,
    }, options);

    return this.each(function () {
        const input = this;

        // Only <input> elements, and never initialise the same one twice.
        if (input.tagName !== 'INPUT' || input.dataset.passwordStrength === 'true') {
            return;
        }
        input.dataset.passwordStrength = 'true';

        const $input = $(input);
        const $bar = $('<div>', {
            'class': 'progress-bar',
            role: 'progressbar',
            'aria-valuenow': 0,
            'aria-valuemin': 0,
            'aria-valuemax': 100,
        });
        const $text = $('<span>', {'class': 'password-strength__text'}).html('&nbsp;');
        const $widget = $('<div>', {'class': 'password-strength'})
            .append($('<div>', {'class': 'progress'}).append($bar))
            .append($text);

        $input.closest('div').append($widget);

        function setBar(percent, className) {
            $bar.css('width', `${percent}%`).attr('aria-valuenow', percent)
                .removeClass('bg-danger bg-warning bg-success').addClass(className || '');
        }

        // 'input' (not just 'keyup') so paste and autofill also update the meter.
        $input.on('input change', function () {
            const password = $input.val();

            if (!password.length) {
                setBar(0);
                $text.html('&nbsp;');
                return;
            }

            if (password.length < settings.minimumLength) {
                setBar(10, 'bg-danger');
                $text.text(settings.shortPass);
                return;
            }

            const score = scorePassword(password);

            if (score < 34) {
                setBar(score, 'bg-danger');
                $text.text(settings.badPass);
            } else if (score < 68) {
                setBar(score, 'bg-warning');
                $text.text(settings.goodPass);
            } else {
                setBar(score, 'bg-success');
                $text.text(settings.strongPass);
            }
        });
    });
};
