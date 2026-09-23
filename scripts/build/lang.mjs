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

    const buildLocales = parseBuildLocales(process.env.BUILD_LOCALES);
    // Parsing en_US takes ~20s, so it is read at most once and only when a bundle needs it
    let fallbackGroups = null;
    const getFallbackGroups = () => fallbackGroups ??= readLocaleGroups(langRoot, 'en_US');

    const built = [];
    for (const locale of locales) {
        if (!shouldBuildLocale(locale, process.env.APP_ENV, buildLocales)) {
            continue;
        }

        // Mirrors Laravel's server-side __() fallback to en_US, so a key a locale's translators
        // haven't caught up on yet still renders instead of showing the raw key.
        const groups = locale === 'en_US'
            ? getFallbackGroups()
            : mergeTranslationsWithFallback(readLocaleGroups(langRoot, locale), getFallbackGroups());

        const messages = {};
        for (const [group, value] of Object.entries(groups)) {
            messages[`${locale}.${group}`] = value;
        }

        // Same runtime behavior as the old generated bundles: populate the Lang instance that
        // bootstrap.js (app-{version}.js) created with empty messages.
        // The locale is set explicitly: Lang.js otherwise infers it from <html lang>, which drops
        // the `_ai` suffix the message keys carry, so every lookup in an *_ai locale misses (#4566)
        let code = `(function () {
    var locale = ${JSON.stringify(locale)};
    var messages = ${JSON.stringify(messages)};
    if (typeof window !== 'undefined' && window.Lang) {
        if (window.lang && typeof window.lang.setMessages === 'function') {
            window.lang.setMessages(messages);
            window.lang.setLocale(locale);
        } else {
            window.lang = new window.Lang({messages: messages, locale: locale});
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

    const skipped = locales.length - built.length;
    if (skipped > 0) {
        console.log(`lang: built ${built.join(', ')} - set BUILD_LOCALES=<locale>[,<locale>] or 'all' to also build the other ${skipped}`);
    }

    const missing = buildLocales.filter(locale => locale !== 'all' && !locales.includes(locale));
    if (missing.length > 0) {
        console.warn(`BUILD_LOCALES: no lang/ directory for ${missing.join(', ')} - nothing built for those`);
    }

    return built;
}

/**
 * @param {string|undefined} value Comma-separated locale names, or `all`.
 * @returns {string[]}
 */
export function parseBuildLocales(value) {
    return (value ?? '').split(',').map(entry => entry.trim()).filter(Boolean);
}

/**
 * A full run over every locale takes minutes, which makes `npm run watch` unusable, so a local
 * build does en_US only unless BUILD_LOCALES asks for more - that opt-in is the only way to see a
 * non-en_US locale in dev at all, since the page 404s on a bundle that was never built (#4566).
 *
 * @param {string}            locale       The locale directory under lang/.
 * @param {string|undefined}  appEnv       The APP_ENV the build runs under.
 * @param {string[]}          buildLocales Locales explicitly requested via BUILD_LOCALES.
 * @returns {boolean}
 */
export function shouldBuildLocale(locale, appEnv, buildLocales) {
    if (appEnv !== 'local') {
        return true;
    }

    return locale === 'en_US' || buildLocales.includes('all') || buildLocales.includes(locale);
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

/**
 * Parses every `lang/<locale>/*.php` file into a map of group name (the file's basename) to its
 * parsed translations. Returns an empty map when the locale has no lang/ directory.
 *
 * @param {string} langRoot
 * @param {string} locale
 * @returns {Object<string, *>}
 */
function readLocaleGroups(langRoot, locale) {
    const localeDir = path.join(langRoot, locale);
    if (!fs.existsSync(localeDir)) {
        return {};
    }

    const groups = {};
    const files  = fs.readdirSync(localeDir).filter(file => file.endsWith('.php')).sort();
    for (const file of files) {
        const source = fs.readFileSync(path.join(localeDir, file), 'utf8');

        groups[path.basename(file, '.php')] = parsePhpTranslationFile(source);
    }

    return groups;
}

/**
 * Deep-merges a locale's parsed translations under the fallback's (en_US's), key by key and
 * group by group, so a bundle carries every key the fallback has even where the locale's own
 * file omits it. A locale value wins whenever the key exists on its side — including an empty
 * string, which Laravel treats as an existing translation rather than a missing one — so only a
 * key genuinely absent from the locale is filled from the fallback. A value that isn't a plain
 * object (a scalar, or a PHP list array) is taken from whichever side has it wholesale, never
 * merged element-wise; Laravel differs there (it falls back per list index, and treats an empty
 * array as missing), which no lang file currently relies on.
 *
 * @param {*} localeValue
 * @param {*} fallbackValue
 * @returns {*}
 */
export function mergeTranslationsWithFallback(localeValue, fallbackValue) {
    if (localeValue === undefined) {
        return fallbackValue;
    }
    if (fallbackValue === undefined) {
        return localeValue;
    }
    if (!isPlainObject(localeValue) || !isPlainObject(fallbackValue)) {
        return localeValue;
    }

    const merged = {};
    for (const key of new Set([...Object.keys(fallbackValue), ...Object.keys(localeValue)])) {
        merged[key] = mergeTranslationsWithFallback(localeValue[key], fallbackValue[key]);
    }

    return merged;
}

/**
 * @param {*} value
 * @returns {boolean} Whether value is a plain (associative) object, as opposed to a PHP list
 * array, a scalar, or null.
 */
function isPlainObject(value) {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}
