// @vitest-environment node
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import Lang from 'lang.js';
import {describe, expect, it} from 'vitest';
import {buildLangBundles, parsePhpTranslationFile} from './lang.mjs';

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
