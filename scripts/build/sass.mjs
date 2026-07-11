import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath, pathToFileURL} from 'node:url';
import * as sass from 'sass';
import {transformSync} from 'esbuild';

/**
 * Compiles the four sass entries into public/css (replaces mix.sass + sass-loader).
 *
 * Parity notes vs the old webpack pipeline:
 * - `$asset-url` is prepended to every entry exactly like sass-loader's `additionalData` did.
 * - Webpack tilde imports (`~bootstrap/scss/bootstrap`) and bare package imports
 *   (`lightslider/dist/css/lightslider.min.css`) resolve into node_modules via the custom
 *   importer below.
 * - Plain `.css` imports are inlined the way css-loader did. Dart-sass emits `.css`-suffixed
 *   `@import` urls literally without consulting importers, so the extension is stripped from
 *   loaded scss first; the extensionless url then goes through the importer, which resolves the
 *   `.css` file and inlines it.
 * - urls are never rewritten, matching `processCssUrls: false`.
 * - Dev builds get linked .map files; production is minified (esbuild) and ships no maps.
 */
const sassEntries = [
    ['resources/assets/sass/app.scss', 'app'],
    ['resources/assets/sass/theme/theme.scss', 'theme'],
    ['resources/assets/sass/custom/custom.scss', 'custom-compiled'],
    ['resources/assets/sass/custom/assets/assets.scss', 'assets-compiled'],
];

/**
 * @param {string} rootDir
 * @param {string} version
 * @param {boolean} production
 */
export function buildSassBundles(rootDir, version, production) {
    const outDir = path.join(rootDir, 'public', 'css');
    fs.mkdirSync(outDir, {recursive: true});

    const importer = createSassImporter(rootDir);

    for (const [entry, outName] of sassEntries) {
        const entryPath = path.join(rootDir, entry);
        // Same injection sass-loader's additionalData used to do (CDN base for the sprite urls)
        const source = `$asset-url: "${process.env.ASSETS_BASE_URL}";\n`
            + rewritePlainCssImports(fs.readFileSync(entryPath, 'utf8'));

        const result = sass.compileString(source, {
            url: pathToFileURL(entryPath),
            // `importer` handles loads relative to the containing file (sass hands those in as
            // pre-resolved file: urls); when that misses, the raw url (`~package/...` or bare
            // package paths) falls through to the `importers` list for node_modules resolution
            importer,
            importers: [importer],
            sourceMap: !production,
            sourceMapIncludeSources: true,
            // The scss (bootstrap 4, fontawesome, bootswatch, and our own sheets) is
            // @import-based with legacy color functions throughout; migrating to @use /
            // color.adjust is out of scope for the build migration (#3449)
            silenceDeprecations: ['import', 'global-builtin', 'color-functions'],
            quietDeps: true,
        });

        const outFile = path.join(outDir, `${outName}-${version}.css`);
        let css = hoistPlainCssImports(result.css);

        if (production) {
            css = transformSync(css, {loader: 'css', minify: true}).code;
        } else if (result.sourceMap) {
            fs.writeFileSync(`${outFile}.map`, JSON.stringify(result.sourceMap));
            css += `\n/*# sourceMappingURL=${path.basename(outFile)}.map */`;
        }

        fs.writeFileSync(outFile, css);
    }
}

/**
 * Strips the `.css` extension from `@import` urls (except remote and `url()` ones, which are
 * plain-CSS imports in webpack too) so dart-sass routes them through the importer instead of
 * emitting them literally.
 *
 * @param {string} scss
 * @returns {string}
 */
export function rewritePlainCssImports(scss) {
    return scss.split('\n').map(line => {
        if (!/^\s*@import\b/.test(line)) {
            return line;
        }

        return line.replace(/(?<!url\()(['"])([^'"]+?)\.css\1/g,
            (match, quote, url) => /^(https?:)?\/\//.test(url) ? match : `${quote}${url}${quote}`);
    }).join('\n');
}

/**
 * Moves plain-CSS `@import` statements (the bootswatch google-fonts urls) to the top of the
 * stylesheet, deduplicated, like webpack's css-loader used to. Dart-sass emits them where they
 * occur in the source — nested inside the `.darkly { ... }` theme blocks — where browsers
 * ignore them, since CSS requires `@import` to precede all other rules.
 *
 * @param {string} css
 * @returns {string}
 */
export function hoistPlainCssImports(css) {
    const imports = [];
    const body    = css.replace(/@import\s+(?:url\([^)]*\)|"[^"]*"|'[^']*')[^;{}]*;/g, statement => {
        if (!imports.includes(statement)) {
            imports.push(statement);
        }

        return '';
    });

    if (imports.length === 0) {
        return css;
    }

    // @charset must remain the very first statement
    const charset = body.match(/^@charset[^;]*;\s*/);
    const prefix  = charset === null ? '' : charset[0];

    return prefix + imports.join('\n') + '\n' + body.slice(prefix.length);
}

/**
 * Importer replicating the webpack sass-loader resolution the stylesheets were written against:
 * relative paths first (sass hands those in pre-resolved as file: urls), then node_modules —
 * with or without the webpack `~` prefix.
 *
 * @param {string} rootDir
 * @returns {import('sass').Importer<'sync'>}
 */
export function createSassImporter(rootDir) {
    const nodeModules = path.join(rootDir, 'node_modules');

    return {
        canonicalize(url) {
            if (url.startsWith('file:')) {
                return resolveSassFile(fileURLToPath(new URL(url)));
            }

            return resolveSassFile(path.join(nodeModules, url.startsWith('~') ? url.slice(1) : url));
        },
        load(canonicalUrl) {
            const file     = fileURLToPath(canonicalUrl);
            const contents = fs.readFileSync(file, 'utf8');

            if (file.endsWith('.css')) {
                return {contents, syntax: 'css'};
            }

            return {
                contents: rewritePlainCssImports(contents),
                syntax:   file.endsWith('.sass') ? 'indented' : 'scss',
            };
        },
    };
}

/**
 * Standard sass file resolution for a possibly extensionless path: partials, the three
 * extensions, and directory indexes.
 *
 * @param {string} file
 * @returns {URL|null}
 */
function resolveSassFile(file) {
    const dir  = path.dirname(file);
    const base = path.basename(file);

    const candidates = [];
    if (/\.(scss|sass|css)$/.test(base)) {
        candidates.push(base, `_${base}`);
    } else {
        for (const ext of ['.scss', '.sass', '.css']) {
            candidates.push(`${base}${ext}`, `_${base}${ext}`);
        }
        candidates.push(path.join(base, '_index.scss'), path.join(base, 'index.scss'));
    }

    for (const candidate of candidates) {
        const candidatePath = path.join(dir, candidate);
        if (fs.existsSync(candidatePath) && fs.statSync(candidatePath).isFile()) {
            return pathToFileURL(candidatePath);
        }
    }

    return null;
}
