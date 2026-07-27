// ---------------------------------------------------------------------------
// Regression coverage for #3669, in the spirit of the jquery4-plugin-audit
// (#3590): guard a bundled dependency's export shape against a version bump
// that silently breaks it.
//
// @simonwep/pickr 1.10.0 (bumped from 1.9.1 in #3618) declares "type": "module"
// while its `require` export condition still points at dist/pickr.min.js, which
// contains UMD code. Because of the package type that file is parsed as ESM, so
// it exports nothing at all: `require('@simonwep/pickr')` yields an empty module
// and `Pickr.create` is undefined. That threw
// `TypeError: Pickr.create is not a function` inside SettingsTabMap#activate(),
// which aborted the rest of Map#activate() and the InlineManager dependency
// cascade behind it - i.e. most of the map editor sidebar never initialised.
//
// bootstrap.js therefore imports Pickr as ESM. This test bundles that exact
// usage with the esbuild settings that drive module resolution in
// scripts/build/app.mjs (bundle + format + platform) and evaluates the result,
// so a future bump that breaks the export shape fails here rather than in
// production.
// ---------------------------------------------------------------------------

const fs = require('node:fs');
const path = require('node:path');
const util = require('node:util');

// esbuild asserts `new TextEncoder().encode('') instanceof Uint8Array` on load, which fails under
// jsdom: its TextEncoder and its Uint8Array come from different realms. Line up all three on Node's
// implementations before requiring esbuild. Pickr itself still needs the jsdom DOM, so switching
// the whole file to the node environment isn't an option.
global.TextEncoder = util.TextEncoder;
global.TextDecoder = util.TextDecoder;
global.Uint8Array = new util.TextEncoder().encode('').constructor;

const {buildSync} = require('esbuild');

const rootDir = path.resolve(__dirname, '..', '..', '..');

/**
 * Bundles a snippet with the resolution-relevant settings scripts/build/app.mjs uses for app.js,
 * and returns the generated code.
 */
function bundle(contents) {
    const result = buildSync({
        absWorkingDir: rootDir,
        stdin: {contents, resolveDir: path.join(rootDir, 'resources', 'assets', 'js'), loader: 'js'},
        bundle: true,
        write: false,
        format: 'iife',
        platform: 'browser',
        logLevel: 'silent',
    });

    return result.outputFiles[0].text;
}

describe('@simonwep/pickr export shape (#3669)', () => {
    test('pickr_givenTheAppBundleEsmImport_exposesACreateFactory', () => {
        // Arrange
        const code = bundle(`import Pickr from '@simonwep/pickr'; window.Pickr = Pickr;`);

        // Act
        new Function(code)();

        // Assert
        expect(typeof window.Pickr).toBe('function');
        expect(typeof window.Pickr.create).toBe('function');
    });

    test('bootstrapJs_givenPickrIsBundled_doesNotRequireIt', () => {
        // Arrange
        const bootstrapJs = fs.readFileSync(path.join(rootDir, 'resources', 'assets', 'js', 'bootstrap.js'), 'utf8');

        // Act
        const requiresPickr = /require\(\s*['"]@simonwep\/pickr['"]\s*\)/.test(bootstrapJs);

        // Assert: require() resolves the broken CJS condition - see the file header
        expect(requiresPickr).toBe(false);
        expect(bootstrapJs).toMatch(/import\s+Pickr\s+from\s+['"]@simonwep\/pickr['"]/);
    });
});
