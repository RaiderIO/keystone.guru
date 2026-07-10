#!/usr/bin/env node
import 'dotenv/config';
import {spawn, spawnSync} from 'node:child_process';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import chokidar from 'chokidar';
import {resolveVersion} from './version.mjs';
import {precompileHandlebars} from './handlebars.mjs';
import {buildLangBundles} from './lang.mjs';
import {buildConcatBundles} from './concat.mjs';
import {buildCssBundle} from './css-concat.mjs';
import {copyStaticAssets} from './copy.mjs';

/**
 * Build orchestrator (stage 1 of #3449, see #3491).
 *
 * Runs everything that used to live in webpack.mix.js except the app.js bundle and the four sass
 * entries — those still go through laravel-mix, which this script spawns with the resolved version
 * passed via KSG_BUILD_VERSION so both halves agree on output filenames.
 *
 * In --watch mode the node-built bundles are rebuilt on change too (chokidar), while the spawned
 * `mix watch` keeps covering app.js/sass — so `npm run watch` behaves like it always has.
 *
 * Usage: node scripts/build/build.mjs [--production] [--watch]
 */
const rootDir    = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const production = process.argv.includes('--production');
const watch      = process.argv.includes('--watch');

const version = resolveVersion(rootDir);
console.log(`Building version ${version} (${production ? 'production' : 'development'})`);

runNodeSteps();

const mixBin = path.join(rootDir, 'node_modules', '.bin', 'mix');
const mixEnv = {...process.env, KSG_BUILD_VERSION: version};

if (watch) {
    watchNodeSteps();

    const mixProcess = spawn(mixBin, ['watch'], {cwd: rootDir, stdio: 'inherit', env: mixEnv});
    mixProcess.on('close', code => process.exit(code ?? 1));
} else {
    const mixResult = spawnSync(mixBin, production ? ['--production'] : [], {
        cwd: rootDir,
        stdio: 'inherit',
        env: mixEnv,
    });

    process.exit(mixResult.status ?? 1);
}

function runNodeSteps() {
    time('handlebars', () => precompileHandlebars(rootDir, production));
    time('lang', () => buildLangBundles(rootDir, version, production));
    time('js concat', () => buildConcatBundles(rootDir, version, production));
    time('css concat', () => buildCssBundle(rootDir, version, production));
    time('copy', () => copyStaticAssets(rootDir));
}

function watchNodeSteps() {
    // Same polling cadence as the webpack watchOptions above (see webpack.mix.js) — native fs
    // events are unreliable in this WSL setup
    const watchOptions = {ignoreInitial: true, usePolling: true, interval: 2000};

    // Outputs land in public/ and resources/assets/js/handlebars.js, none of which are watched,
    // so rebuilds cannot re-trigger themselves
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

        chokidar.watch(step.paths.map(p => path.join(rootDir, p)), watchOptions)
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

    console.log('Watching custom/, lib/, css/, lang/ and handlebars templates for changes...');
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
