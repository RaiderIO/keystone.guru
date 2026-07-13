import fs from 'node:fs';
import path from 'node:path';
import parser from 'php-array-parser';
import {transformSync} from 'esbuild';

/**
 * Builds the per-locale translation bundles (public/js/lang-{locale}-{version}.js).
 *
 * Replaces the old webpack-only mechanism (generated require.context entries +
 * laravel-localization-loader). laravel-localization-loader is a thin wrapper around
 * php-array-loader, which itself wraps php-array-parser — we use php-array-parser directly and
 * replicate php-array-loader's exact source preprocessing so parsing semantics are identical.
 *
 * @param {string} rootDir
 * @param {string} version
 * @param {boolean} production
 * @returns {string[]} The locales that were built.
 */
export function buildLangBundles(rootDir, version, production) {
    const langRoot = path.join(rootDir, 'lang');
    const outDir   = path.join(rootDir, 'public', 'js');
    fs.mkdirSync(outDir, {recursive: true});

    const locales = fs.readdirSync(langRoot)
        .filter(entry => fs.statSync(path.join(langRoot, entry)).isDirectory());

    const built = [];
    for (const locale of locales) {
        // Same shortcut as the old webpack.mix.js: only build en_US locally
        if (process.env.APP_ENV === 'local' && locale !== 'en_US') {
            continue;
        }

        const messages  = {};
        const localeDir = path.join(langRoot, locale);
        const files     = fs.readdirSync(localeDir).filter(file => file.endsWith('.php')).sort();
        for (const file of files) {
            const source = fs.readFileSync(path.join(localeDir, file), 'utf8');

            messages[`${locale}.${path.basename(file, '.php')}`] = parsePhpTranslationFile(source);
        }

        // Same runtime behavior as the old generated bundles: populate the Lang instance that
        // bootstrap.js (app-{version}.js) created with empty messages
        let code = `(function () {
    var messages = ${JSON.stringify(messages)};
    if (typeof window !== 'undefined' && window.Lang) {
        if (window.lang && typeof window.lang.setMessages === 'function') {
            window.lang.setMessages(messages);
        } else {
            window.lang = new window.Lang({messages: messages});
        }
    }
})();
`;

        if (production) {
            code = transformSync(code, {minify: true}).code;
        }

        fs.writeFileSync(path.join(outDir, `lang-${locale}-${version}.js`), code);
        built.push(locale);
    }

    return built;
}

/**
 * Mirrors php-array-loader's preprocessing verbatim: drop everything up to and including the
 * `return` keyword, replace a trailing `?>` before parsing.
 *
 * @param {string} source
 * @returns {Object}
 */
export function parsePhpTranslationFile(source) {
    const ret = source.indexOf('return') + 'return'.length;
    let expression = source.substr(ret);
    expression = expression.replace(/\?>\s*$/, '_');

    return parser.parse(expression);
}
