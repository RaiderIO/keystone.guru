// @vitest-environment node
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import Lang from 'lang.js';
import {describe, expect, it} from 'vitest';
import {buildLangBundles, parseBuildLocales, parsePhpTranslationFile, shouldBuildLocale} from './lang.mjs';

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

    it('parsePhpTranslationFile_givenRealTranslationFile_returnsNonEmptyObject', () => {
        // A real file from the repo, so the parser is exercised against production input
        const parsed = parsePhpTranslationFile(fs.readFileSync('lang/en_US/auth.php', 'utf8'));

        expect(typeof parsed).toBe('object');
        expect(Object.keys(parsed).length).toBeGreaterThan(0);
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
            const appEnv        = process.env.APP_ENV;
            process.env.APP_ENV = 'testing';
            try {
                buildLangBundles(rootDir, 'v1', false);
            } finally {
                if (appEnv === undefined) {
                    delete process.env.APP_ENV;
                } else {
                    process.env.APP_ENV = appEnv;
                }
            }

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
        const previous = {APP_ENV: process.env.APP_ENV, BUILD_LOCALES: process.env.BUILD_LOCALES};

        try {
            for (const locale of locales) {
                fs.mkdirSync(path.join(rootDir, 'lang', locale), {recursive: true});
                fs.writeFileSync(path.join(rootDir, 'lang', locale, 'js.php'), "<?php\n\nreturn ['edit_label' => 'x'];\n");
            }

            for (const [key, value] of Object.entries(env)) {
                if (value === undefined) {
                    delete process.env[key];
                } else {
                    process.env[key] = value;
                }
            }

            return buildLangBundles(rootDir, 'v1', false).sort();
        } finally {
            for (const [key, value] of Object.entries(previous)) {
                if (value === undefined) {
                    delete process.env[key];
                } else {
                    process.env[key] = value;
                }
            }
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
});
