import fs from 'node:fs';
import path from 'node:path';
import fg from 'fast-glob';
import {transformSync} from 'esbuild';

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

    const files = fg.sync('resources/assets/css/**/*.css', {cwd: rootDir, absolute: true}).sort();

    let code = files.map(file => fs.readFileSync(file, 'utf8')).join('\n');

    if (production) {
        code = transformSync(code, {minify: true, loader: 'css'}).code;
    }

    fs.writeFileSync(path.join(outDir, `custom-${version}.css`), code);
}
