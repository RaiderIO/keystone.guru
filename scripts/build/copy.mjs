import fs from 'node:fs';
import path from 'node:path';

/**
 * Static copies into public/ (replaces the mix.copy calls).
 *
 * @param {string} rootDir
 */
export function copyStaticAssets(rootDir) {
    const copies = [
        ['node_modules/@fortawesome/fontawesome-free/webfonts', 'public/webfonts'],
        ['resources/assets/webfonts', 'public/webfonts'],
        ['resources/assets/vendor', 'public/vendor'],
    ];

    for (const [from, to] of copies) {
        fs.cpSync(path.join(rootDir, from), path.join(rootDir, to), {recursive: true, force: true});
    }
}
