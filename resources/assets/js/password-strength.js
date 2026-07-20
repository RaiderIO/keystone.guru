// ---------------------------------------------------------------------------
// $.fn.passwordStrength (#3597)
//
// Self-owned replacement for the unmaintained `password-strength-meter` npm
// plugin (last release 2020, last GitHub push 2022). Renders a Bootstrap
// progress bar + text tip below a password <input> and scores the typed
// value with zxcvbn-ts (the actively-maintained continuation of Dropbox's
// zxcvbn), instead of the former plugin's ad-hoc character-class heuristic.
//
//     $('#register_password').passwordStrength({
//         shortPass: '...', badPass: '...', goodPass: '...', strongPass: '...',
//         minimumLength: 8,
//     });
//
// The widget is appended to the input's closest wrapping <div> (mirrors the
// former plugin's default `closestSelector: 'div'`) and updates on every
// input change. Widget visuals live in resources/assets/css/lib/password-strength.css.
// ---------------------------------------------------------------------------

const $ = require('jquery');
const {ZxcvbnFactory, debounce} = require('@zxcvbn-ts/core');

// The language dictionaries are large (~1-2MB of ranked word lists each), so building the
// factory is deferred until a page actually initialises a password field, rather than paying
// that cost on every page load via bootstrap.js.
let zxcvbnFactory = null;

function getZxcvbnFactory() {
    if (zxcvbnFactory === null) {
        const zxcvbnCommonPackage = require('@zxcvbn-ts/language-common');
        const zxcvbnEnPackage = require('@zxcvbn-ts/language-en');

        zxcvbnFactory = new ZxcvbnFactory({
            dictionary: {
                ...zxcvbnCommonPackage.dictionary,
                ...zxcvbnEnPackage.dictionary,
            },
            graphs: zxcvbnCommonPackage.adjacencyGraphs,
            translations: zxcvbnEnPackage.translations,
        });
    }

    return zxcvbnFactory;
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

    // zxcvbn's 0-4 score collapsed to the former plugin's 3-tier weak/medium/strong scale,
    // rendered with Bootstrap's own contextual colors so the bar matches every other
    // progress bar in the app (e.g. resources/views/misc/mapping.blade.php).
    const scoreTiers = [
        {className: 'bg-danger', text: settings.badPass},
        {className: 'bg-danger', text: settings.badPass},
        {className: 'bg-warning', text: settings.goodPass},
        {className: 'bg-warning', text: settings.goodPass},
        {className: 'bg-success', text: settings.strongPass},
    ];
    const tierClassNames = [...new Set(scoreTiers.map((tier) => tier.className))].join(' ');

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
                .removeClass(tierClassNames).addClass(className || '');
        }

        // zxcvbn's dictionary-backed scoring is materially heavier per keystroke than the
        // former plugin's regex heuristic (measurably so on longer passwords), so debounce it
        // with the library's own helper rather than scoring on every single keystroke.
        const renderScore = debounce(function (password) {
            const tier = scoreTiers[getZxcvbnFactory().check(password).score];

            setBar(((scoreTiers.indexOf(tier) + 1) / scoreTiers.length) * 100, tier.className);
            $text.text(tier.text);
        }, 200);

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

            renderScore(password);
        });
    });
};
