#!/usr/bin/env node
import 'dotenv/config';
import {spawnSync} from 'node:child_process';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
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
 * Usage: node scripts/build/build.mjs [--production] [--watch]
 */
const rootDir    = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const production = process.argv.includes('--production');
const watch      = process.argv.includes('--watch');

const version = resolveVersion(rootDir);
console.log(`Building version ${version} (${production ? 'production' : 'development'})`);

runNodeSteps();

if (watch) {
    // Full watch support for the node-built bundles arrives in stage 2 (#3492) when mix is
    // removed. Until then `mix watch` covers app.js/sass; changes to custom/, lang/, css/ and
    // handlebars templates require a manual re-run.
    console.warn('Note: only app.js/sass are watched in stage 1; re-run this script for custom/lang/css changes.');
}

const mixArgs  = watch ? ['watch'] : (production ? ['--production'] : []);
const mixResult = spawnSync(path.join(rootDir, 'node_modules', '.bin', 'mix'), mixArgs, {
    cwd: rootDir,
    stdio: 'inherit',
    env: {...process.env, KSG_BUILD_VERSION: version},
});

process.exit(mixResult.status ?? 1);

function runNodeSteps() {
    time('handlebars', () => precompileHandlebars(rootDir, production));
    time('lang', () => buildLangBundles(rootDir, version, production));
    time('js concat', () => buildConcatBundles(rootDir, version, production));
    time('css concat', () => buildCssBundle(rootDir, version, production));
    time('copy', () => copyStaticAssets(rootDir));
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
