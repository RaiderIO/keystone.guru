#!/usr/bin/env node
import 'dotenv/config';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import chokidar from 'chokidar';
import {resolveVersion} from './version.mjs';
import {precompileHandlebars} from './handlebars.mjs';
import {buildLangBundles} from './lang.mjs';
import {buildConcatBundles} from './concat.mjs';
import {buildCssBundle} from './css-concat.mjs';
import {copyStaticAssets} from './copy.mjs';
import {buildAppBundle} from './app.mjs';
import {buildSassBundles} from './sass.mjs';

/**
 * Build orchestrator (stage 2 of #3449, see #3491/#3492).
 *
 * Everything webpack.mix.js used to do now runs here: the node steps extracted in stage 1 plus
 * the esbuild app.js bundle and the four sass entries — laravel-mix and webpack are gone.
 *
 * In --watch mode every step is rebuilt on change (chokidar).
 *
 * Usage: node scripts/build/build.mjs [--production] [--watch]
 */
const rootDir    = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const production = process.argv.includes('--production');
const watch      = process.argv.includes('--watch');

const version = resolveVersion(rootDir);
console.log(`Building version ${version} (${production ? 'production' : 'development'})`);

runBuildSteps();

if (watch) {
    watchBuildSteps();
}

function runBuildSteps() {
    time('handlebars', () => precompileHandlebars(rootDir, production));
    time('lang', () => buildLangBundles(rootDir, version, production));
    time('js concat', () => buildConcatBundles(rootDir, version, production));
    time('css concat', () => buildCssBundle(rootDir, version, production));
    time('copy', () => copyStaticAssets(rootDir));
    time('app bundle', () => buildAppBundle(rootDir, version, production));
    time('sass', () => buildSassBundles(rootDir, version, production));
}

function watchBuildSteps() {
    // Same polling cadence as the old webpack watchOptions — native fs events are unreliable in
    // this WSL setup
    const watchOptions = {ignoreInitial: true, usePolling: true, interval: 2000};

    // Outputs land in public/, none of which is watched. The generated
    // resources/assets/js/handlebars.js is inside the app-bundle watch path but explicitly
    // ignored there (it is a js-concat input, not an app.js input), so rebuilds cannot
    // re-trigger themselves
    const steps = [
        {
            name: 'handlebars + js concat',
            paths: ['resources/assets/js/handlebars'],
            run: () => {
                precompileHandlebars(rootDir, production);
                buildConcatBundles(rootDir, version, production);
            },
        },
        {
            name: 'js concat',
            paths: ['resources/assets/js/custom', 'resources/assets/lib'],
            run: () => buildConcatBundles(rootDir, version, production),
        },
        {
            name: 'app bundle',
            paths: ['resources/assets/js'],
            // custom/ and handlebars are covered by the concat steps above; tests aren't bundled
            ignored: file => file.includes(`${path.sep}js${path.sep}custom`)
                || file.includes(`${path.sep}js${path.sep}handlebars`)
                || file.endsWith('.test.js'),
            run: () => buildAppBundle(rootDir, version, production),
        },
        {
            name: 'sass',
            paths: ['resources/assets/sass'],
            run: () => buildSassBundles(rootDir, version, production),
        },
        {
            name: 'css concat',
            paths: ['resources/assets/css'],
            run: () => buildCssBundle(rootDir, version, production),
        },
        {
            name: 'lang',
            paths: ['lang'],
            run: () => buildLangBundles(rootDir, version, production),
        },
        {
            name: 'copy',
            paths: ['resources/assets/webfonts', 'resources/assets/vendor'],
            run: () => copyStaticAssets(rootDir),
        },
    ];

    for (const step of steps) {
        let debounce = null;

        chokidar.watch(step.paths.map(p => path.join(rootDir, p)), {...watchOptions, ignored: step.ignored})
            .on('all', (event, file) => {
                clearTimeout(debounce);
                debounce = setTimeout(() => {
                    console.log(`${path.relative(rootDir, file)} changed (${event})`);
                    try {
                        time(step.name, step.run);
                    } catch (e) {
                        // Keep watching: a mid-save syntax error should not kill the watcher
                        console.error(`  ${step.name} failed: ${e.message}`);
                    }
                }, 200);
            });
    }

    console.log('Watching js/, lib/, sass/, css/, lang/ and handlebars templates for changes...');
}

/**
 * @param {string} label
 * @param {Function} fn
 */
function time(label, fn) {
    const start = performance.now();
    fn();
    console.log(`  ${label}: ${Math.round(performance.now() - start)}ms`);
}
