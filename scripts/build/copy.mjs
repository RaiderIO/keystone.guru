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
        // Only the admin telemetry dashboard loads Chart.js, so it is script-tagged there rather than
        // pulled into the site-wide bundle that every visitor downloads (#4333).
        ['node_modules/chart.js/dist/chart.umd.js', 'public/js/lib/chart.umd.js'],
    ];

    for (const [from, to] of copies) {
        const target = path.join(rootDir, to);
        fs.mkdirSync(path.dirname(target), {recursive: true});
        fs.cpSync(path.join(rootDir, from), target, {recursive: true, force: true});
    }
}
