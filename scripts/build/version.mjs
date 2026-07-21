import {execSync} from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

/**
 * Resolves the build version, mirroring the old webpack.mix.js behavior:
 * - `npm --output_version=<tag> run production` (the release pipeline) wins via npm_config_output_version,
 *   and the repo-root `version` file is left untouched (the release map-context job writes its own).
 * - Otherwise the current git revision is used and written to the repo-root `version` file,
 *   which PHP reads (ViewService, laravel-model-caching config, MapContext SavesToFile).
 *
 * @param {string} rootDir
 * @returns {string}
 */
export function resolveVersion(rootDir) {
    const override = process.env.npm_config_output_version;
    if (typeof override !== 'undefined' && override !== '') {
        return override;
    }

    const version = execSync('git rev-list HEAD -1', {cwd: rootDir}).toString().trim();

    fs.writeFileSync(path.join(rootDir, 'version'), version);

    return version;
}
