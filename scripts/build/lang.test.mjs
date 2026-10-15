// @vitest-environment node
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import Lang from 'lang.js';
import {describe, expect, it, vi} from 'vitest';
import {buildLangBundles, mergeTranslationsWithFallback, parseBuildLocales, parsePhpTranslationFile, shouldBuildLocale} from './lang.mjs';

/**
 * Runs `callback` with the given environment variables set (or unset, for `undefined`), restoring them afterwards.
 *
 * @param {Object} env
 * @param {Function} callback
 * @returns {*} Whatever callback returns.
 */
function withEnv(env, callback) {
    const previous = Object.fromEntries(Object.keys(env).map(key => [key, process.env[key]]));
    const apply    = values => {
        for (const [key, value] of Object.entries(values)) {
            if (value === undefined) {
                delete process.env[key];
            } else {
                process.env[key] = value;
            }
        }
    };

    apply(env);
    try {
        return callback();
    } finally {
        apply(previous);
    }
}

describe('parsePhpTranslationFile', () => {
    it('parsePhpTranslationFile_givenSimpleArray_returnsObject', () => {
        const source = `<?php

return [
    'title' => 'Keystone.guru',
    'count' => 5,
];
`;

        expect(parsePhpTranslationFile(source)).toEqual({title: 'Keystone.guru', count: 5});
    });

    it('parsePhpTranslationFile_givenNestedArraysAndEscapes_returnsNestedObject', () => {
        const source = `<?php
// Translations used by the menu
return array(
    'menu' => [
        'home' => "It's \\"here\\"",
        'items' => ['a', 'b'],
    ],
);
`;

        expect(parsePhpTranslationFile(source)).toEqual({
            menu: {
                home: 'It\'s "here"',
                items: ['a', 'b'],
            },
        });
    });

    it.each([
        ['followed by a newline', '?>\n'],
        ['at the very end', '?>'],
    ])('parsePhpTranslationFile_givenClosingPhpTag_%s_returnsObject', (_, closingTag) => {
        // Arrange
        const source = `<?php

return [
    'title' => 'Keystone.guru',
];
${closingTag}`;

        // Act
        const parse = () => parsePhpTranslationFile(source);

        // Assert
        expect(parse).not.toThrow();
        expect(parse()).toEqual({title: 'Keystone.guru'});
    });

    it('parsePhpTranslationFile_givenRealTranslationFile_returnsNonEmptyObject', () => {
        // A real file from the repo, so the parser is exercised against production input
        const parsed = parsePhpTranslationFile(fs.readFileSync(path.join(import.meta.dirname, '..', '..', 'lang', 'en_US', 'auth.php'), 'utf8'));

        expect(typeof parsed).toBe('object');
        expect(parsed.failed).toEqual(expect.stringContaining('credentials'));
    });
});

describe('mergeTranslationsWithFallback', () => {
    it('mergeTranslationsWithFallback_givenLocaleMissingAKey_returnsFallbackValue', () => {
        expect(mergeTranslationsWithFallback({}, {edit_label: 'Edit'})).toEqual({edit_label: 'Edit'});
    });

    it('mergeTranslationsWithFallback_givenLocaleMissingANestedKey_returnsFallbackValueForIt', () => {
        const locale   = {menu: {home: 'Домівка'}};
        const fallback = {menu: {home: 'Home', settings: 'Settings'}};

        expect(mergeTranslationsWithFallback(locale, fallback)).toEqual({
            menu: {home: 'Домівка', settings: 'Settings'},
        });
    });

    it('mergeTranslationsWithFallback_givenLocaleHasTheKey_keepsTheLocaleValue', () => {
        expect(mergeTranslationsWithFallback({edit_label: 'Редагувати'}, {edit_label: 'Edit'}))
            .toEqual({edit_label: 'Редагувати'});
    });

    it('mergeTranslationsWithFallback_givenLocaleValueIsAnEmptyString_returnsFallbackValue', () => {
        // Mirrors the server side's EmptyTranslationFallbackTranslator: an empty translation is an untranslated one
        expect(mergeTranslationsWithFallback({edit_label: ''}, {edit_label: 'Edit'})).toEqual({edit_label: 'Edit'});
    });

    it('mergeTranslationsWithFallback_givenLocaleAndFallbackValuesAreEmptyStrings_keepsTheEmptyString', () => {
        expect(mergeTranslationsWithFallback({decimal: ''}, {decimal: ''})).toEqual({decimal: ''});
    });

    it('mergeTranslationsWithFallback_givenEmptyLocaleValueAndNoFallbackKey_keepsTheEmptyString', () => {
        expect(mergeTranslationsWithFallback({edit_label: ''}, {})).toEqual({edit_label: ''});
    });

    it('mergeTranslationsWithFallback_givenEmptyNestedLocaleValue_returnsFallbackValueForIt', () => {
        const locale   = {npcs: {123: 'Kobold', 456: ''}};
        const fallback = {npcs: {123: 'Kobold', 456: 'Gnoll'}};

        expect(mergeTranslationsWithFallback(locale, fallback)).toEqual({npcs: {123: 'Kobold', 456: 'Gnoll'}});
    });

    it('mergeTranslationsWithFallback_givenLocaleValueIsAListArray_takesTheLocaleArrayWhole', () => {
        expect(mergeTranslationsWithFallback({items: ['a']}, {items: ['a', 'b', 'c']})).toEqual({items: ['a']});
    });

    it('mergeTranslationsWithFallback_givenLocaleMissingAWholeGroup_takesTheFallbackGroupWhole', () => {
        const locale   = {js: {edit_label: 'Редагувати'}};
        const fallback = {js: {edit_label: 'Edit'}, auth: {failed: 'Failed'}};

        expect(mergeTranslationsWithFallback(locale, fallback)).toEqual({
            js: {edit_label: 'Редагувати'},
            auth: {failed: 'Failed'},
        });
    });

    it('mergeTranslationsWithFallback_givenFallbackMessages_leavesFallbackObjectUnchanged', () => {
        const fallback         = {js: {edit_label: 'Edit'}};
        const fallbackSnapshot = JSON.parse(JSON.stringify(fallback));

        mergeTranslationsWithFallback({js: {}}, fallback);

        expect(fallback).toEqual(fallbackSnapshot);
    });
});

describe('buildLangBundles', () => {
    /**
     * Runs a generated bundle the way the browser does: bootstrap.js has already created
     * `window.lang` with empty messages, and Lang.js inferred its locale from `<html lang>` —
     * which the layout strips the `_ai` suffix from.
     *
     * @param {string} locale     The locale directory to build a bundle for.
     * @param {string} htmlLang   The value of the `<html lang>` attribute Lang.js inferred.
     * @param {Object} messages   Map of translation file basename to its PHP source.
     * @returns {Lang} The Lang instance the bundle populated.
     */
    function buildAndRunBundle(locale, htmlLang, messages) {
        const rootDir = fs.mkdtempSync(path.join(os.tmpdir(), 'ksg-lang-'));

        try {
            fs.mkdirSync(path.join(rootDir, 'lang', locale), {recursive: true});
            for (const [file, source] of Object.entries(messages)) {
                fs.writeFileSync(path.join(rootDir, 'lang', locale, `${file}.php`), source);
            }

            // The builder skips every non-en_US locale when APP_ENV=local
            withEnv({APP_ENV: 'testing'}, () => buildLangBundles(rootDir, 'v1', false));

            const code   = fs.readFileSync(path.join(rootDir, 'public', 'js', `lang-${locale}-v1.js`), 'utf8');
            const window = {Lang, lang: new Lang({messages: {}, locale: htmlLang})};
            new Function('window', code)(window);

            return window.lang;
        } finally {
            fs.rmSync(rootDir, {recursive: true, force: true});
        }
    }

    it('buildLangBundles_givenAiLocaleWhoseHtmlLangDropsTheSuffix_resolvesTranslations', () => {
        const lang = buildAndRunBundle('de_DE_ai', 'de_DE', {js: "<?php\n\nreturn ['edit_label' => 'Bearbeiten'];\n"});

        expect(lang.get('js.edit_label')).toBe('Bearbeiten');
        expect(lang.messages[`${lang.getLocale()}.js`]).toEqual({edit_label: 'Bearbeiten'});
    });

    it('buildLangBundles_givenNonAiLocale_resolvesTranslations', () => {
        const lang = buildAndRunBundle('en_US', 'en_US', {js: "<?php\n\nreturn ['edit_label' => 'Edit'];\n"});

        expect(lang.get('js.edit_label')).toBe('Edit');
        expect(lang.messages[`${lang.getLocale()}.js`]).toEqual({edit_label: 'Edit'});
    });

    /**
     * Same as buildAndRunBundle, but also writes an en_US lang/ directory so the target locale's
     * bundle is built with the en_US fallback merged in.
     *
     * @param {string} locale         The locale directory to build a bundle for.
     * @param {string} htmlLang       The value of the `<html lang>` attribute Lang.js inferred.
     * @param {Object} localeMessages Map of translation file basename to its PHP source, for `locale`.
     * @param {Object} enUsMessages   Map of translation file basename to its PHP source, for en_US.
     * @returns {Lang} The Lang instance the bundle populated.
     */
    function buildAndRunBundleWithFallback(locale, htmlLang, localeMessages, enUsMessages) {
        const rootDir = fs.mkdtempSync(path.join(os.tmpdir(), 'ksg-lang-'));

        try {
            fs.mkdirSync(path.join(rootDir, 'lang', locale), {recursive: true});
            for (const [file, source] of Object.entries(localeMessages)) {
                fs.writeFileSync(path.join(rootDir, 'lang', locale, `${file}.php`), source);
            }

            fs.mkdirSync(path.join(rootDir, 'lang', 'en_US'), {recursive: true});
            for (const [file, source] of Object.entries(enUsMessages)) {
                fs.writeFileSync(path.join(rootDir, 'lang', 'en_US', `${file}.php`), source);
            }

            withEnv({APP_ENV: 'testing'}, () => buildLangBundles(rootDir, 'v1', false));

            const code   = fs.readFileSync(path.join(rootDir, 'public', 'js', `lang-${locale}-v1.js`), 'utf8');
            const window = {Lang, lang: new Lang({messages: {}, locale: htmlLang})};
            new Function('window', code)(window);

            return window.lang;
        } finally {
            fs.rmSync(rootDir, {recursive: true, force: true});
        }
    }

    it('buildLangBundles_givenNoLangInstanceYet_createsOneWithTheBundlesLocale', () => {
        // Arrange
        const rootDir = fs.mkdtempSync(path.join(os.tmpdir(), 'ksg-lang-'));
        const window  = {Lang};

        try {
            fs.mkdirSync(path.join(rootDir, 'lang', 'de_DE_ai'), {recursive: true});
            fs.writeFileSync(path.join(rootDir, 'lang', 'de_DE_ai', 'js.php'), "<?php\n\nreturn ['edit_label' => 'Bearbeiten'];\n");
            withEnv({APP_ENV: 'testing'}, () => buildLangBundles(rootDir, 'v1', false));

            // Act
            new Function('window', fs.readFileSync(path.join(rootDir, 'public', 'js', 'lang-de_DE_ai-v1.js'), 'utf8'))(window);
        } finally {
            fs.rmSync(rootDir, {recursive: true, force: true});
        }

        // Assert
        expect(window.lang).toBeInstanceOf(Lang);
        expect(window.lang.getLocale()).toBe('de_DE_ai');
        expect(window.lang.get('js.edit_label')).toBe('Bearbeiten');
    });

    it('buildLangBundles_givenProductionFlag_writesAMinifiedBundleThatStillPopulatesLang', () => {
        // Arrange
        const rootDir = fs.mkdtempSync(path.join(os.tmpdir(), 'ksg-lang-'));
        let code;

        try {
            fs.mkdirSync(path.join(rootDir, 'lang', 'en_US'), {recursive: true});
            fs.writeFileSync(path.join(rootDir, 'lang', 'en_US', 'js.php'), "<?php\n\nreturn ['edit_label' => 'Edit'];\n");

            // Act
            withEnv({APP_ENV: 'testing'}, () => buildLangBundles(rootDir, 'v1', true));
            code = fs.readFileSync(path.join(rootDir, 'public', 'js', 'lang-en_US-v1.js'), 'utf8');
        } finally {
            fs.rmSync(rootDir, {recursive: true, force: true});
        }

        // Assert
        expect(code).not.toContain('\n    ');
        const window = {Lang, lang: new Lang({messages: {}, locale: 'en_US'})};
        new Function('window', code)(window);
        expect(window.lang.get('js.edit_label')).toBe('Edit');
    });

    it('buildLangBundles_givenLocaleMissingAKeyEnUsHas_resolvesTheEnUsValue', () => {
        const lang = buildAndRunBundleWithFallback(
            'uk_UA',
            'uk_UA',
            {js: "<?php\n\nreturn ['edit_label' => 'Редагувати'];\n"},
            {js: "<?php\n\nreturn ['edit_label' => 'Edit', 'add_to_collection_label' => 'Add to collection'];\n"},
        );

        expect(lang.get('js.edit_label')).toBe('Редагувати');
        expect(lang.get('js.add_to_collection_label')).toBe('Add to collection');
    });

    it('buildLangBundles_givenGroupAndKeyOnlyTheLocaleHas_keepsThem', () => {
        const lang = buildAndRunBundleWithFallback(
            'uk_UA',
            'uk_UA',
            {
                js:    "<?php\n\nreturn ['edit_label' => 'Редагувати', 'uk_only_label' => 'Лише тут'];\n",
                extra: "<?php\n\nreturn ['note' => 'Нотатка'];\n",
            },
            {js: "<?php\n\nreturn ['edit_label' => 'Edit'];\n"},
        );

        expect(lang.get('js.uk_only_label')).toBe('Лише тут');
        expect(lang.get('extra.note')).toBe('Нотатка');
    });
});

describe('shouldBuildLocale', () => {
    it('shouldBuildLocale_givenNonLocalEnv_buildsEveryLocale', () => {
        expect(shouldBuildLocale('de_DE_ai', 'production', [])).toBe(true);
        expect(shouldBuildLocale('en_US', undefined, [])).toBe(true);
    });

    it('shouldBuildLocale_givenLocalEnvWithoutBuildLocales_buildsOnlyEnUs', () => {
        expect(shouldBuildLocale('en_US', 'local', [])).toBe(true);
        expect(shouldBuildLocale('de_DE_ai', 'local', [])).toBe(false);
    });

    it('shouldBuildLocale_givenLocalEnvWithRequestedLocale_buildsThatLocaleAndEnUs', () => {
        expect(shouldBuildLocale('de_DE_ai', 'local', ['de_DE_ai'])).toBe(true);
        expect(shouldBuildLocale('en_US', 'local', ['de_DE_ai'])).toBe(true);
        expect(shouldBuildLocale('ru_RU_ai', 'local', ['de_DE_ai'])).toBe(false);
    });

    it('shouldBuildLocale_givenLocalEnvWithAll_buildsEveryLocale', () => {
        expect(shouldBuildLocale('ru_RU_ai', 'local', ['all'])).toBe(true);
    });
});

describe('parseBuildLocales', () => {
    it('parseBuildLocales_givenPaddedCommaSeparatedList_returnsTrimmedNames', () => {
        expect(parseBuildLocales(' de_DE_ai , ru_RU_ai ')).toEqual(['de_DE_ai', 'ru_RU_ai']);
    });

    it('parseBuildLocales_givenUnsetOrEmpty_returnsEmptyList', () => {
        expect(parseBuildLocales(undefined)).toEqual([]);
        expect(parseBuildLocales('')).toEqual([]);
        expect(parseBuildLocales(',,')).toEqual([]);
    });
});

describe('buildLangBundles locale selection', () => {
    /**
     * @param {Object} env      APP_ENV / BUILD_LOCALES to build under.
     * @param {string[]} locales Locale directories to populate under lang/.
     * @returns {string[]} The locales the builder reported as built.
     */
    function buildUnder(env, locales) {
        const rootDir = fs.mkdtempSync(path.join(os.tmpdir(), 'ksg-lang-'));

        try {
            for (const locale of locales) {
                fs.mkdirSync(path.join(rootDir, 'lang', locale), {recursive: true});
                fs.writeFileSync(path.join(rootDir, 'lang', locale, 'js.php'), "<?php\n\nreturn ['edit_label' => 'x'];\n");
            }

            return withEnv(env, () => buildLangBundles(rootDir, 'v1', false)).sort();
        } finally {
            fs.rmSync(rootDir, {recursive: true, force: true});
        }
    }

    it('buildLangBundles_givenLocalEnvWithoutBuildLocales_writesOnlyEnUs', () => {
        expect(buildUnder({APP_ENV: 'local', BUILD_LOCALES: undefined}, ['en_US', 'de_DE_ai'])).toEqual(['en_US']);
    });

    it('buildLangBundles_givenLocalEnvWithBuildLocales_writesTheRequestedLocaleToo', () => {
        expect(buildUnder({APP_ENV: 'local', BUILD_LOCALES: 'de_DE_ai'}, ['en_US', 'de_DE_ai', 'ru_RU_ai'])).toEqual(['de_DE_ai', 'en_US']);
    });

    it('buildLangBundles_givenNonLocalEnv_writesEveryLocale', () => {
        expect(buildUnder({APP_ENV: 'production', BUILD_LOCALES: undefined}, ['en_US', 'de_DE_ai'])).toEqual(['de_DE_ai', 'en_US']);
    });

    it('buildLangBundles_givenBuildLocalesNamingAMissingLocale_warnsAboutIt', () => {
        const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});
        vi.spyOn(console, 'log').mockImplementation(() => {});

        const built = buildUnder({APP_ENV: 'local', BUILD_LOCALES: 'de_DE_ai,xx_XX'}, ['en_US', 'de_DE_ai']);

        expect(built).toEqual(['de_DE_ai', 'en_US']);
        expect(warn).toHaveBeenCalledTimes(1);
        expect(warn.mock.calls[0][0]).toContain('no lang/ directory for xx_XX');
        expect(warn.mock.calls[0][0]).not.toContain('de_DE_ai');
    });

    it('buildLangBundles_givenBuildLocalesAll_buildsEveryLocaleWithoutWarning', () => {
        const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});

        const built = buildUnder({APP_ENV: 'local', BUILD_LOCALES: 'all'}, ['en_US', 'de_DE_ai', 'ru_RU_ai']);

        expect(built).toEqual(['de_DE_ai', 'en_US', 'ru_RU_ai']);
        expect(warn).not.toHaveBeenCalled();
    });
});
