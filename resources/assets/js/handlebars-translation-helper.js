/**
 * Registers the `t` helper on a Handlebars instance: {{t 'key'}} renders lang/<locale>/js.php's `key`;
 * use {{{t 'key'}}} for translations holding markup. The global `lang` is resolved at render time,
 * so messages loaded after registration (the lang-<locale>-<version>.js bundle) are picked up.
 *
 * @param {Object} handlebars The Handlebars instance to register the helper on.
 * @returns {void}
 */
function registerTranslationHelper(handlebars) {
    handlebars.registerHelper('t', function (key) {
        return lang.get('js.' + key);
    });
}

module.exports = {registerTranslationHelper};
