// @vitest-environment node
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import {afterEach, beforeEach, describe, expect, it} from 'vitest';
import {buildCssBundle} from './css-concat.mjs';

describe('buildCssBundle', () => {
    let rootDir;

    beforeEach(() => {
        rootDir = fs.mkdtempSync(path.join(os.tmpdir(), 'css-concat-test-'));

        writeCssFile(rootDir, 'resources/assets/css/sections/zzz.css', '.section { color: red; }');
        writeCssFile(rootDir, 'resources/assets/css/lib/aaa.css', '.lib { color: green; }');
        writeCssFile(rootDir, 'resources/assets/css/base/aaa.css', '.base { color: blue; }');
    });

    afterEach(() => {
        fs.rmSync(rootDir, {recursive: true, force: true});
    });

    it('buildCssBundle_givenFilesInEveryBucket_concatenatesBaseBeforeLibBeforeSections', () => {
        buildCssBundle(rootDir, 'test', false);

        const code = fs.readFileSync(path.join(rootDir, 'public/css/custom-test.css'), 'utf8');

        expect(code.indexOf('.base')).toBeLessThan(code.indexOf('.lib'));
        expect(code.indexOf('.lib')).toBeLessThan(code.indexOf('.section'));
    });

    it('buildCssBundle_givenProductionFlag_minifiesOutput', () => {
        buildCssBundle(rootDir, 'test', true);

        const code = fs.readFileSync(path.join(rootDir, 'public/css/custom-test.css'), 'utf8');

        expect(code).not.toContain('    ');
    });
});

/**
 * @param {string} rootDir
 * @param {string} relativePath
 * @param {string} contents
 */
function writeCssFile(rootDir, relativePath, contents) {
    const filePath = path.join(rootDir, relativePath);
    fs.mkdirSync(path.dirname(filePath), {recursive: true});
    fs.writeFileSync(filePath, contents);
}
