// @vitest-environment node
import path from 'node:path';
import {describe, expect, it} from 'vitest';
import {expandScriptList} from './concat.mjs';
import {customScripts} from './custom-scripts-order.mjs';

const rootDir = path.resolve(import.meta.dirname, '..', '..');

describe('expandScriptList', () => {
    it('expandScriptList_givenCustomScripts_preservesHandOrderAndExpandsTrailingGlob', () => {
        const files = expandScriptList(rootDir, customScripts);

        // Plain entries keep their hand-maintained position: precompiled handlebars first,
        // then the no-dependency utilities
        expect(files[0]).toBe(path.join(rootDir, 'resources/assets/js/handlebars.js'));
        expect(files[1]).toBe(path.join(rootDir, 'resources/assets/js/custom/colorutil.js'));

        // The trailing inline glob expands to actual files, after every explicit entry
        const explicitCount = customScripts.length - 1;
        const inlineFiles = files.slice(explicitCount);
        expect(inlineFiles.length).toBeGreaterThan(100);
        expect(inlineFiles.every(file => /custom[\/\\]inline[\/\\].+[\/\\].+\.js$/.test(file))).toBe(true);

        // Deterministic (sorted) glob expansion so builds are reproducible
        expect(inlineFiles).toEqual([...inlineFiles].sort());
    });

    it('expandScriptList_givenPlainPaths_returnsThemUnsortedInGivenOrder', () => {
        const files = expandScriptList(rootDir, ['b.js', 'a.js']);

        expect(files).toEqual([path.join(rootDir, 'b.js'), path.join(rootDir, 'a.js')]);
    });
});
