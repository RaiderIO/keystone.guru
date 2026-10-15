// ---------------------------------------------------------------------------
// Covers the Handlebars `t` helper that bootstrap.js registers on the bundle's
// Handlebars and test/setup.js registers on the test runtime's.
// ---------------------------------------------------------------------------

const Handlebars = require('handlebars');
const {registerTranslationHelper} = require('./handlebars-translation-helper');

const originalLang = globalThis.lang;

/**
 * @param {Object} messages Keys (without the `js.` prefix) mapped to their translation.
 * @returns {{get: Function}}
 */
function createLang(messages) {
    return {get: vi.fn((key) => messages[key.replace(/^js\./, '')] ?? key)};
}

/**
 * @param {string} source
 * @returns {Function}
 */
function compileWithTranslationHelper(source) {
    const handlebars = Handlebars.create();
    registerTranslationHelper(handlebars);

    return handlebars.compile(source);
}

afterEach(() => {
    globalThis.lang = originalLang;
});

describe('registerTranslationHelper', () => {
    test('registerTranslationHelper_givenKey_rendersJsTranslation', () => {
        // Arrange
        globalThis.lang = createLang({save: 'Save'});
        const template = compileWithTranslationHelper(`<button>{{t 'save'}}</button>`);

        // Act
        const html = template({});

        // Assert
        expect(globalThis.lang.get).toHaveBeenCalledWith('js.save');
        expect(html).toBe('<button>Save</button>');
    });

    test('registerTranslationHelper_givenLangReplacedAfterRegistration_rendersThroughCurrentLang', () => {
        // Arrange
        globalThis.lang = createLang({save: 'Save'});
        const template = compileWithTranslationHelper(`{{t 'save'}}`);
        globalThis.lang = createLang({save: 'Speichern'});

        // Act
        const html = template({});

        // Assert
        expect(html).toBe('Speichern');
    });

    test('registerTranslationHelper_givenDoubleStash_escapesMarkup', () => {
        // Arrange
        globalThis.lang = createLang({warning: '<b>Careful</b>'});
        const template = compileWithTranslationHelper(`{{t 'warning'}}`);

        // Act
        const html = template({});

        // Assert
        expect(html).toBe('&lt;b&gt;Careful&lt;/b&gt;');
    });

    test('registerTranslationHelper_givenTripleStash_rendersMarkup', () => {
        // Arrange
        globalThis.lang = createLang({warning: '<b>Careful</b>'});
        const template = compileWithTranslationHelper(`{{{t 'warning'}}}`);

        // Act
        const html = template({});

        // Assert
        expect(html).toBe('<b>Careful</b>');
    });
});
