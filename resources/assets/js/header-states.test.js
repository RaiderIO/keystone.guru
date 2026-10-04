// ---------------------------------------------------------------------------
// The site header's game version row, dungeon strip and mobile dungeon sheet
// share one state language: muted text at rest, full contrast when current,
// with the colours coming from theme variables. These tests compile the real
// theme stylesheet, load it with header.css in bundle order, and assert the
// resulting cascade - jsdom leaves var() unresolved, so each assertion names
// the variable that won.
// ---------------------------------------------------------------------------

const fs = require('fs');
const path = require('path');
const sass = require('sass');

const ROOT = path.resolve(__dirname, '../../..');

const themeCss = sass.compile(path.join(ROOT, 'resources/assets/sass/theme/theme.scss'), {
    silenceDeprecations: ['import'],
}).css;
const headerCss = fs.readFileSync(path.join(ROOT, 'resources/assets/css/sections/header.css'), 'utf8');
const discoverCss = fs.readFileSync(path.join(ROOT, 'resources/assets/css/sections/discover.css'), 'utf8');
const mapHeaderCss = fs.readFileSync(path.join(ROOT, 'resources/assets/css/sections/map-header.css'), 'utf8');
// Bootstrap's reboot as each theme compiles it, wrapped in the theme class; it outranks a single-class button rule
const themeRebootCss = '.darkly button { margin: 0; border-radius: 0; }';

const THEMES = ['darkly', 'lux', 'vapor'];

/**
 * @param {string} theme
 * @returns {Object<string, string>} The theme's --theme-* custom properties, keyed by name
 */
function themeVariables(theme) {
    const block = themeCss.match(new RegExp(`:root\\.${theme} \\{([^}]*)\\}`))[1];

    return Object.fromEntries([...block.matchAll(/(--theme-[a-z-]+):\s*([^;]+);/g)].map(match => [match[1], match[2].trim()]));
}

/**
 * @param {Object<string, string>} variables
 * @param {string} name
 * @returns {string}
 */
function resolveColour(variables, name) {
    const value = variables[name];
    const reference = value.match(/^var\((--theme-[a-z-]+)\)$/);

    return reference === null ? value : resolveColour(variables, reference[1]);
}

/**
 * @param {string} hex #rgb or #rrggbb
 * @returns {number}
 */
function relativeLuminance(hex) {
    const digits = hex.length === 4 ? hex.slice(1).split('').map(digit => digit + digit) : hex.slice(1).match(/../g);
    const [r, g, b] = digits.map(pair => parseInt(pair, 16) / 255)
        .map(channel => channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4);

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

/**
 * @param {string} foreground
 * @param {string} background
 * @returns {number}
 */
function contrastRatio(foreground, background) {
    const [lighter, darker] = [relativeLuminance(foreground), relativeLuminance(background)].sort((a, b) => b - a);

    return (lighter + 0.05) / (darker + 0.05);
}

/**
 * Loads the theme and header stylesheets in bundle order under a darkly root and renders the given markup.
 *
 * @param {string} html
 */
function renderHeader(html) {
    document.documentElement.className = 'theme darkly';
    document.head.innerHTML = `<style>${themeCss}</style><style>${themeRebootCss}</style><style>${headerCss}</style>`
        + `<style>${discoverCss}</style><style>${mapHeaderCss}</style>`;
    document.body.innerHTML = html;
}

/**
 * Renders one abbreviated retail tile as common/dungeon/list/card.blade.php does in the header.
 */
function renderRetailTile() {
    renderHeader(`
        <div class="dungeon_context_header discover">
            <div class="row"><div class="list_dungeon col selectable"><div class="card-img-caption">
                <a href="#" id="link">
                    <span class="card-text text-white dungeon_card_dungeon_name" id="abbreviation" aria-hidden="true">DON</span>
                    <span class="card-text text-white dungeon_card_dungeon_full_name" id="full_name">Den of Nalorakk</span>
                    <img class="card-img-top" src="" alt=""/>
                </a>
            </div></div></div>
        </div>`);
}

describe('header state language', () => {
    afterEach(() => {
        document.documentElement.className = '';
        document.head.innerHTML = '';
        document.body.innerHTML = '';
    });

    test.each(THEMES)('themeTextMuted_given%sTheme_clearsAaContrastOnTheHeaderAndThePage', theme => {
        // Arrange
        const variables = themeVariables(theme);

        // Act
        const muted = resolveColour(variables, '--theme-text-muted');

        // Assert: the chips and versions sit on the page band, the menus and the sheet on the header surface
        expect(contrastRatio(muted, resolveColour(variables, '--theme-header'))).toBeGreaterThanOrEqual(4.5);
        expect(contrastRatio(muted, resolveColour(variables, '--theme-darker'))).toBeGreaterThanOrEqual(4.5);
    });

    test('gameVersionAndChip_givenCurrentAndNotCurrent_rendersContrastOnlyForTheCurrentOne', () => {
        // Arrange
        renderHeader(`
            <div class="game_version_header">
                <a class="game_version" id="version_current" aria-current="true" href="#">Retail</a>
                <a class="game_version" id="version_other" href="#">Classic</a>
                <div class="dungeon_strip">
                    <a class="dungeon_strip_chip" id="chip_current" aria-current="true" href="#">DME</a>
                    <a class="dungeon_strip_chip" id="chip_other" href="#">BFD</a>
                </div>
            </div>`);

        // Act
        const colourOf = id => getComputedStyle(document.getElementById(id)).color;

        // Assert
        expect(colourOf('version_current')).toBe('var(--theme-text-contrast)');
        expect(colourOf('chip_current')).toBe('var(--theme-text-contrast)');
        expect(colourOf('version_other')).toBe('var(--theme-text-muted)');
        expect(colourOf('chip_other')).toBe('var(--theme-text-muted)');
    });

    test('mutedText_givenEveryHeaderSurface_usesTheThemeMutedVariable', () => {
        // Arrange: the version segments also render in the navbar menu, outside the sheet
        renderHeader(`
            <div class="ksg-header"><span class="ksg-nav-entry-desc" id="nav_description">Find routes</span></div>
            <div class="game_version_header"><span class="dungeon_strip_group_label" id="group_label">Raids</span></div>
            <nav class="game_version_segments"><a class="game_version_segment" id="segment" href="#">Classic</a></nav>
            <div class="dungeon_sheet"><span class="dungeon_sheet_row_abbreviation" id="abbreviation">BFD</span></div>`);

        // Act
        const colourOf = id => getComputedStyle(document.getElementById(id)).color;

        // Assert
        expect(colourOf('nav_description')).toBe('var(--theme-text-muted)');
        expect(colourOf('group_label')).toBe('var(--theme-text-muted)');
        expect(colourOf('segment')).toBe('var(--theme-text-muted)');
        expect(colourOf('abbreviation')).toBe('var(--theme-text-muted)');
    });

    test('sheetRow_givenCurrent_isMarkedByItsBorderAlone', () => {
        // Arrange
        renderHeader(`
            <div class="dungeon_sheet">
                <a class="dungeon_sheet_row border-accent" id="row_current" aria-current="true" href="#">Blackfathom Deeps</a>
                <a class="dungeon_sheet_row" id="row_other" href="#">Blackrock Depths</a>
            </div>`);

        // Act
        const current = getComputedStyle(document.getElementById('row_current'));
        const other = getComputedStyle(document.getElementById('row_other'));

        // Assert: the accent border (.border-accent) alone - neither the hover tint nor a heavier weight, which the
        // desktop chips and tiles do not use either
        expect(current.backgroundColor).toBe(other.backgroundColor);
        expect(current.fontWeight).toBe(other.fontWeight);
    });

    test('allButton_givenThemeButtonReboot_keepsTheChipsRadius', () => {
        // Arrange
        renderHeader(`
            <div class="game_version_header">
                <div class="dungeon_strip">
                    <a class="dungeon_strip_chip" id="chip" href="#">BFD</a>
                    <button type="button" class="dungeon_strip_all" id="all">All 32</button>
                </div>
            </div>`);

        // Act
        const radiusOf = id => getComputedStyle(document.getElementById(id)).borderRadius;

        // Assert
        expect(radiusOf('all')).toBe(radiusOf('chip'));
        expect(radiusOf('all')).toBe('0.25rem');
    });

    test('retailTile_givenAnyTheme_keepsAGapBetweenItsBorderAndTheArt', () => {
        // Arrange
        renderHeader(`
            <div class="dungeon_context_header">
                <div class="row"><div class="list_dungeon col selectable selected border-accent" id="tile"></div></div>
            </div>`);

        // Act
        const tile = getComputedStyle(document.getElementById('tile'));

        // Assert: the art is dark in every theme, and lux's accent is black - flush on the art it would vanish
        // jsdom resolves this padding to px but leaves other rem lengths as written
        expect(['0.125rem', '2px']).toContain(tile.paddingTop);
        expect(['0.125rem', '2px']).toContain(tile.paddingLeft);
    });

    test('retailTile_givenRest_showsTheAbbreviationAndHidesTheFullName', () => {
        // Arrange
        renderRetailTile();

        // Act
        const opacityOf = id => getComputedStyle(document.getElementById(id)).opacity;

        // Assert
        expect(opacityOf('full_name')).toBe('0');
        expect(['', '1']).toContain(opacityOf('abbreviation'));
    });

    test('retailTile_givenKeyboardFocus_showsTheFullNameInPlaceOfTheAbbreviation', () => {
        // Arrange
        renderRetailTile();

        // Act
        document.getElementById('link').focus();
        const opacityOf = id => getComputedStyle(document.getElementById(id)).opacity;

        // Assert
        expect(opacityOf('full_name')).toBe('1');
        expect(opacityOf('abbreviation')).toBe('0');
    });

    test('readoutName_givenItsLongNameFit_changesSizeWithoutATransition', () => {
        // Arrange
        renderHeader(`
            <div class="game_version_header"><div class="dungeon_strip">
                <div class="dungeon_strip_readout"><span class="dungeon_strip_readout_name" id="name">Dire Maul East</span></div>
            </div></div>`);

        // Act
        const name = getComputedStyle(document.getElementById('name'));

        // Assert: DungeonStrip.fitReadoutName() measures right after toggling the smaller size, so it must apply at once
        expect(name.transitionProperty).not.toContain('font-size');
        expect(name.transition ?? '').not.toContain('font-size');
    });

    test('stripGroups_givenAFocusedClippedChip_clipRatherThanScroll', () => {
        // Arrange
        renderHeader(`
            <div class="game_version_header"><div class="dungeon_strip">
                <div class="dungeon_strip_groups" id="groups"></div>
            </div></div>`);

        // Act
        const groups = getComputedStyle(document.getElementById('groups'));

        // Assert: a hidden box scrolls to a focused chip, so DungeonStrip never sees it clipped and never unfolds
        expect(groups.overflow).toBe('clip');
    });
});
