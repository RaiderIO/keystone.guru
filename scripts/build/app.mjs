import path from 'node:path';
import {buildSync} from 'esbuild';

/**
 * Bundles resources/assets/js/app.js into public/js/app-{version}.js (replaces mix.js).
 *
 * bootstrap.js mixes hoisted ESM imports with in-place require() calls whose order is
 * load-bearing: window.$ / window.jQuery must be assigned before the ~15 jQuery plugins are
 * required. esbuild preserves in-place CommonJS execution order and emits a plain IIFE script,
 * exactly like the webpack bundle did — this is why esbuild was chosen over Vite/Rollup, whose
 * ESM import hoisting breaks the plugin registration order (#2232, #3449).
 *
 * @param {string} rootDir
 * @param {string} version
 * @param {boolean} production
 */
export function buildAppBundle(rootDir, version, production) {
    buildSync({
        absWorkingDir: rootDir,
        entryPoints: ['resources/assets/js/app.js'],
        bundle: true,
        outfile: path.join('public', 'js', `app-${version}.js`),
        format: 'iife',
        platform: 'browser',
        // Handlebars has a bug which requires this: https://github.com/wycats/handlebars.js/issues/1174
        alias: {handlebars: 'handlebars/dist/handlebars.min.js'},
        minify: production,
        // Dev builds get a linked .map like mix produced; production ships none
        sourcemap: production ? false : 'linked',
        // Keep third-party license comments at the end of the bundle (webpack emitted them as a
        // separate app-{version}.js.LICENSE.txt file)
        legalComments: 'eof',
        logLevel: 'warning',
    });
}
