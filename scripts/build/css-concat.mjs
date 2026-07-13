import fs from 'node:fs';
import path from 'node:path';
import {transformSync} from 'esbuild';
import {expandScriptList} from './concat.mjs';
import {customStyles} from './custom-styles-order.mjs';

/**
 * Builds public/css/custom-{version}.css from resources/assets/css (replaces mix.styles).
 *
 * @param {string} rootDir
 * @param {string} version
 * @param {boolean} production
 */
export function buildCssBundle(rootDir, version, production) {
    const outDir = path.join(rootDir, 'public', 'css');
    fs.mkdirSync(outDir, {recursive: true});

    const files = expandScriptList(rootDir, customStyles);

    let code = files.map(file => fs.readFileSync(file, 'utf8')).join('\n');

    if (production) {
        code = transformSync(code, {minify: true, loader: 'css'}).code;
    }

    fs.writeFileSync(path.join(outDir, `custom-${version}.css`), code);
}
