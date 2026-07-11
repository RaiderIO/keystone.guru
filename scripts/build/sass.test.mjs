// @vitest-environment node
import path from 'node:path';
import {fileURLToPath, pathToFileURL} from 'node:url';
import {describe, expect, it} from 'vitest';
import {createSassImporter, hoistPlainCssImports, rewritePlainCssImports} from './sass.mjs';

const rootDir = path.resolve(import.meta.dirname, '..', '..');

describe('rewritePlainCssImports', () => {
    it('rewritePlainCssImports_givenCssExtensionImport_stripsTheExtension', () => {
        expect(rewritePlainCssImports('@import "~leaflet/dist/leaflet.css";'))
            .toBe('@import "~leaflet/dist/leaflet";');
        expect(rewritePlainCssImports("@import '~datatables.net-dt/css/dataTables.dataTables.min.css';"))
            .toBe("@import '~datatables.net-dt/css/dataTables.dataTables.min';");
    });

    it('rewritePlainCssImports_givenRemoteOrUrlImport_leavesItAlone', () => {
        expect(rewritePlainCssImports('@import "https://example.com/font.css";'))
            .toBe('@import "https://example.com/font.css";');
        expect(rewritePlainCssImports('@import url("fonts.css");'))
            .toBe('@import url("fonts.css");');
    });

    it('rewritePlainCssImports_givenNonImportLines_leavesThemAlone', () => {
        const scss = '.icon { background: url("sprite.css"); } // not an @import\n@import "noty";';

        expect(rewritePlainCssImports(scss)).toBe(scss);
    });
});

describe('hoistPlainCssImports', () => {
    it('hoistPlainCssImports_givenNestedImport_hoistsItAboveRulesKeepingCharsetFirst', () => {
        const css = '@charset "UTF-8";\n.darkly {\n  @import url(https://fonts.example/css2?family=Lato;700);\n}\n.other { color: red; }';

        const result = hoistPlainCssImports(css);

        expect(result.startsWith('@charset "UTF-8";\n@import url(https://fonts.example/css2?family=Lato;700);\n')).toBe(true);
        expect(result.indexOf('@import')).toBeLessThan(result.indexOf('.darkly'));
    });

    it('hoistPlainCssImports_givenDuplicateImports_deduplicatesThem', () => {
        const css = '.a { @import "https://fonts.example/lato.css"; }\n.b { @import "https://fonts.example/lato.css"; }';

        const result = hoistPlainCssImports(css);

        expect(result.match(/@import/g)).toHaveLength(1);
    });

    it('hoistPlainCssImports_givenNoImports_returnsCssUnchanged', () => {
        const css = '.a { color: red; }';

        expect(hoistPlainCssImports(css)).toBe(css);
    });
});

describe('createSassImporter', () => {
    const importer = createSassImporter(rootDir);

    it('canonicalize_givenTildeUrl_resolvesIntoNodeModules', () => {
        const url = importer.canonicalize('~bootstrap/scss/bootstrap');

        expect(url).not.toBeNull();
        expect(fileURLToPath(url)).toBe(path.join(rootDir, 'node_modules', 'bootstrap', 'scss', 'bootstrap.scss'));
    });

    it('canonicalize_givenBarePackageUrl_resolvesIntoNodeModules', () => {
        const url = importer.canonicalize('lightslider/dist/css/lightslider.min');

        expect(url).not.toBeNull();
        expect(fileURLToPath(url)).toBe(path.join(rootDir, 'node_modules', 'lightslider', 'dist', 'css', 'lightslider.min.css'));
    });

    it('canonicalize_givenFileUrlToPartial_resolvesTheUnderscorePartial', () => {
        const url = importer.canonicalize(pathToFileURL(path.join(rootDir, 'resources/assets/sass/variables')).href);

        expect(url).not.toBeNull();
        expect(fileURLToPath(url)).toBe(path.join(rootDir, 'resources', 'assets', 'sass', '_variables.scss'));
    });

    it('canonicalize_givenUnresolvableUrl_returnsNull', () => {
        expect(importer.canonicalize('~does-not-exist/nope')).toBeNull();
    });

    it('load_givenCssFile_returnsCssSyntaxWithoutRewriting', () => {
        const cssUrl = importer.canonicalize('~leaflet/dist/leaflet');

        const result = importer.load(cssUrl);

        expect(result.syntax).toBe('css');
        expect(result.contents).toContain('.leaflet-container');
    });

    it('load_givenScssFile_rewritesNestedCssExtensionImports', () => {
        const scssUrl = importer.canonicalize(pathToFileURL(path.join(rootDir, 'resources/assets/sass/app.scss')).href);

        const result = importer.load(scssUrl);

        expect(result.syntax).toBe('scss');
        expect(result.contents).toContain('@import "~leaflet/dist/leaflet";');
        expect(result.contents).not.toContain('leaflet.css');
    });
});
