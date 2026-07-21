import {execFileSync} from 'node:child_process';
import path from 'node:path';

/**
 * Precompiles all .handlebars templates into resources/assets/js/handlebars.js, which is the
 * first entry of the custom-{version}.js concat (replaces the old webpack-shell-plugin-next
 * onBuildStart hook).
 *
 * @param {string} rootDir
 * @param {boolean} production Minify the precompiled templates (-m), matching the old build.
 */
export function precompileHandlebars(rootDir, production) {
    const args = [];
    if (production) {
        args.push('-m');
    }
    args.push('resources/assets/js/handlebars/', '-f', 'resources/assets/js/handlebars.js');

    execFileSync(path.join(rootDir, 'node_modules', '.bin', 'handlebars'), args, {
        cwd: rootDir,
        stdio: 'inherit',
    });
}
