import fs from 'node:fs';
import path from 'node:path';
import fg from 'fast-glob';
import {transformSync} from 'esbuild';
import {customScripts, libScriptsGlob} from './custom-scripts-order.mjs';

/**
 * Builds public/js/custom-{version}.js and public/js/lib-{version}.js (replaces mix.scripts /
 * mix.babel).
 *
 * The inputs are global-scope scripts (bare top-level classes/functions, no modules) whose
 * concatenation order is load-bearing, and InlineManager instantiates classes via eval(className).
 * The output must therefore stay a plain script, and top-level names must survive minification —
 * esbuild's transform API guarantees that: unlike its bundler, it never renames top-level symbols.
 *
 * @param {string} rootDir
 * @param {string} version
 * @param {boolean} production
 */
export function buildConcatBundles(rootDir, version, production) {
    const outDir = path.join(rootDir, 'public', 'js');
    fs.mkdirSync(outDir, {recursive: true});

    writeBundle(rootDir, expandScriptList(rootDir, customScripts), path.join(outDir, `custom-${version}.js`), production);
    writeBundle(rootDir, expandScriptList(rootDir, [libScriptsGlob]), path.join(outDir, `lib-${version}.js`), production);
}

/**
 * Expands glob entries in the ordered script list to concrete paths (alphabetical, so builds are
 * deterministic), keeping plain paths in their hand-ordered position.
 *
 * @param {string} rootDir
 * @param {string[]} entries
 * @returns {string[]} Absolute file paths.
 */
export function expandScriptList(rootDir, entries) {
    const files = [];
    for (const entry of entries) {
        if (fg.isDynamicPattern(entry)) {
            files.push(...fg.sync(entry, {cwd: rootDir, absolute: true}).sort());
        } else {
            files.push(path.join(rootDir, entry));
        }
    }

    return files;
}

/**
 * @param {string} rootDir
 * @param {string[]} files
 * @param {string} outFile
 * @param {boolean} production
 */
function writeBundle(rootDir, files, outFile, production) {
    let code = files.map(file => fs.readFileSync(file, 'utf8')).join('\n');

    if (production) {
        code = transformSync(code, {minify: true}).code;
    }

    fs.writeFileSync(outFile, code);
}
