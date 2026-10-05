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
// vapor.scss paints the header's info button gold with !important, and Bootstrap's own menu item states, each as
// the theme bundle compiles them under the theme class
const themeButtonCss = '.darkly .bg-header .btn-info { background-color: var(--theme-primary) !important; }'
    + '.darkly .dropdown-item.active, .darkly .dropdown-item:active { color: #fff; background-color: #ea39b8; }'
    + '.darkly .dropdown-item:hover, .darkly .dropdown-item:focus { color: #32fbe2; background-color: #4f4f4f; }';

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

    if (reference !== null) {
        return resolveColour(variables, reference[1]);
    }

    return {black: '#000', white: '#fff'}[value] ?? value;
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
 * @param {string} hex #rgb or #rrggbb
 * @returns {number[]} The red, green and blue channels, 0-255
 */
function hexChannels(hex) {
    const digits = hex.length === 4 ? hex.slice(1).split('').map(digit => digit + digit) : hex.slice(1).match(/../g);

    return digits.map(pair => parseInt(pair, 16));
}

/**
 * Paints an rgba() tint over an opaque colour.
 *
 * @param {string} tint rgba(r, g, b, a)
 * @param {string} backdrop #rgb or #rrggbb
 * @returns {string} #rrggbb
 */
function compositeTint(tint, backdrop) {
    const [r, g, b, alpha] = tint.match(/[\d.]+/g).map(Number);

    return '#' + hexChannels(backdrop)
        .map((channel, index) => Math.round([r, g, b][index] * alpha + channel * (1 - alpha)))
        .map(channel => channel.toString(16).padStart(2, '0'))
        .join('');
}

/**
 * jsdom does not cascade into pseudo-elements, so their declarations are read from the stylesheets.
 *
 * @param {string} selector
 * @returns {CSSStyleDeclaration[]} The style of every rule with exactly this selector, in source order
 */
function rulesFor(selector) {
    return [...document.styleSheets]
        .flatMap(sheet => [...sheet.cssRules])
        .filter(rule => rule.selectorText !== undefined
            && rule.selectorText.split(',').map(part => part.trim()).includes(selector))
        .map(rule => rule.style);
}

/**
 * Loads the theme and header stylesheets in bundle order under a darkly root and renders the given markup.
 *
 * @param {string} html
 */
function renderHeader(html) {
    document.documentElement.className = 'theme darkly';
    document.head.innerHTML = `<style>${themeCss}</style><style>${themeRebootCss}</style><style>${themeButtonCss}</style>`
        + `<style>${headerCss}</style>`
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

    test.each(THEMES)('headerControls_given%sTheme_clearAaContrastInEveryState', theme => {
        // Arrange
        const variables = themeVariables(theme);
        const ratio = (foreground, background) => contrastRatio(resolveColour(variables, foreground), resolveColour(variables, background));

        // Act
        const ratios = {
            loginAtRest: ratio('--theme-btn-info-text', '--theme-btn-info'),
            loginHovered: ratio('--theme-btn-info-text', '--theme-btn-info-hover'),
            createRouteAtRest: ratio('--theme-btn-accent-text', '--theme-btn-accent'),
            createRouteHovered: ratio('--theme-btn-accent-hover-text', '--theme-btn-accent-hover'),
            menuHeading: ratio('--theme-dropdown-header', '--theme-dark'),
            menuItemHovered: ratio('--theme-dropdown-item-hover-text', '--theme-dropdown-item-hover'),
            menuItemCurrent: ratio('--theme-dropdown-item-active-text', '--theme-dropdown-item-active'),
            aiMarker: ratio('--theme-ai-marker', '--theme-darker'),
        };

        // Assert: the menus sit on --theme-dark, the AI marker on its --theme-darker pill
        expect(Object.entries(ratios).filter(([, value]) => !(value >= 4.5))).toEqual([]);
    });

    test('loginButton_givenTheThemesGoldHeaderButton_takesTheHeaderInfoVariables', () => {
        // Arrange
        renderHeader(`
            <div class="ksg-header"><nav class="navbar-second"><div class="bg-header"><ul class="navbar-nav">
                <li><a class="btn btn-info" id="login" href="#">Login</a></li>
            </ul></div></nav></div>`);

        // Act
        const login = getComputedStyle(document.getElementById('login'));

        // Assert
        expect(login.backgroundColor).toBe('var(--theme-btn-info)');
        expect(login.color).toBe('var(--theme-btn-info-text)');
    });

    test('menu_givenHeadingFocusedAndCurrentItems_takesTheHeaderMenuVariables', () => {
        // Arrange
        renderHeader(`
            <div class="ksg-header"><nav class="navbar-second"><div class="dropdown"><div class="dropdown-menu show">
                <h6 class="dropdown-header" id="heading">Preferences</h6>
                <a class="dropdown-item ksg-nav-entry" id="focused" href="#">
                    <span class="ksg-nav-entry-desc" id="focused_description">Find routes</span>
                </a>
                <a class="dropdown-item active" id="current" href="#">My routes</a>
                <a class="ksg-nav-prefs-language" href="#"><span class="ksg-nav-prefs-ai" id="ai">AI</span></a>
            </div></div></nav></div>`);
        document.getElementById('focused').focus();

        // Act
        const styleOf = id => getComputedStyle(document.getElementById(id));

        // Assert
        expect(styleOf('heading').color).toBe('var(--theme-dropdown-header)');
        expect(styleOf('focused').backgroundColor).toBe('var(--theme-dropdown-item-hover)');
        // jsdom ranks a selector list by its most specific member, so the theme's `#map .dropdown-item:hover,
        // .dropdown-item:focus` black wins the focused item's colour here, though not in a browser
        expect(styleOf('focused_description').color).toBe(styleOf('focused').color);
        expect(styleOf('current').backgroundColor).toBe('var(--theme-dropdown-item-active)');
        expect(styleOf('current').color).toBe('var(--theme-dropdown-item-active-text)');
        expect(styleOf('ai').color).toBe('var(--theme-ai-marker)');
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

    test('viewsChip_givenAFullViewShare_paintsNoFillUnderItsLabel', () => {
        // Arrange
        renderHeader(`
            <div class="game_version_header"><div class="dungeon_strip">
                <a class="dungeon_strip_chip dungeon_strip_chip--views" id="chip" href="#" style="--dungeon-strip-view-share: 100%">BRD</a>
            </div></div>`);

        // Act
        const chip = getComputedStyle(document.getElementById('chip'));
        const bar = rulesFor('.dungeon_strip_chip--views::after');

        // Assert: the chip keeps its plain tint, and the share is drawn by a bar in the label's own colour
        expect(chip.backgroundColor).toBe('var(--dungeon-strip-control-bg)');
        expect(['', 'none']).toContain(chip.backgroundImage);
        expect(bar).toHaveLength(1);
        expect(bar[0].backgroundColor.toLowerCase()).toBe('currentcolor');
        expect(bar[0].scale).toBe('var(--dungeon-strip-view-share) 1');
    });

    test.each([false, true])('viewBar_givenShrunkHeader%s_staysBelowTheLabelsLineBox', shrunk => {
        // Arrange
        renderHeader(`
            <div class="ksg-header ${shrunk ? 'ksg-header--shrink' : ''}"><div class="game_version_header"><div class="dungeon_strip">
                <a class="dungeon_strip_chip dungeon_strip_chip--views" id="chip" href="#" style="--dungeon-strip-view-share: 100%">BRD</a>
            </div></div></div>`);
        const chip = getComputedStyle(document.getElementById('chip'));
        const rem = name => parseFloat(chip.getPropertyValue(name));

        // Act: the label is one line of line-height 1, centred between the chip's 0.125rem borders
        const roomUnderLabel = (rem('--dungeon-strip-chip-height') - 2 * 0.125 - rem('--dungeon-strip-chip-font-size')) / 2;
        const barReach = rem('--dungeon-strip-view-bar-bottom') + rem('--dungeon-strip-view-bar-height');

        // Assert
        expect(rulesFor('.dungeon_strip_chip--views::after')[0].bottom).toBe('var(--dungeon-strip-view-bar-bottom)');
        expect(rulesFor('.dungeon_strip_chip--views::after')[0].height).toBe('var(--dungeon-strip-view-bar-height)');
        expect(barReach).toBeGreaterThan(0);
        expect(barReach).toBeLessThanOrEqual(roomUnderLabel);
    });

    test('chip_givenKeyboardFocus_rendersFullContrastText', () => {
        // Arrange
        renderHeader(`
            <div class="game_version_header"><div class="dungeon_strip">
                <a class="dungeon_strip_chip" id="chip_focused" href="#">BRD</a>
                <a class="dungeon_strip_chip" id="chip_other" href="#">BFD</a>
            </div></div>`);

        // Act
        document.getElementById('chip_focused').focus();
        const colourOf = id => getComputedStyle(document.getElementById(id)).color;

        // Assert: focus takes the hover tint, which muted text does not clear in any theme
        expect(colourOf('chip_focused')).toBe('var(--theme-text-contrast)');
        expect(colourOf('chip_other')).toBe('var(--theme-text-muted)');
    });

    test.each(THEMES)('viewsChip_given%sTheme_keepsItsLabelAndBarReadableInEveryState', theme => {
        // Arrange
        renderHeader(`
            <div class="game_version_header"><div class="dungeon_strip">
                <a class="dungeon_strip_chip dungeon_strip_chip--views" id="chip_rest" href="#" style="--dungeon-strip-view-share: 100%">BRD</a>
                <a class="dungeon_strip_chip dungeon_strip_chip--views" id="chip_current" aria-current="true" href="#" style="--dungeon-strip-view-share: 100%">BFD</a>
                <a class="dungeon_strip_chip dungeon_strip_chip--views" id="chip_focused" href="#" style="--dungeon-strip-view-share: 100%">GNO</a>
            </div></div>`);
        document.getElementById('chip_focused').focus();
        const variables = themeVariables(theme);
        const strip = getComputedStyle(document.querySelector('.dungeon_strip'));
        // The strip sits on the page band; hover shares the focus tint and the theme's full-contrast hover text
        const band = resolveColour(variables, '--theme-darker');
        const states = ['chip_rest', 'chip_current', 'chip_focused'].map(id => {
            const chip = getComputedStyle(document.getElementById(id));
            const tint = strip.getPropertyValue(chip.backgroundColor.match(/^var\((--[a-z-]+)\)$/)[1]).trim();

            return {id, text: chip.color.match(/^var\((--theme-[a-z-]+)\)$/)[1], background: compositeTint(tint, band)};
        });
        const barColour = rulesFor('.dungeon_strip_chip--views::after')[0].backgroundColor;

        // Act
        const ratios = states.map(state => {
            const text = resolveColour(variables, state.text);
            const bar = barColour.toLowerCase() === 'currentcolor' ? text : resolveColour(variables, barColour.match(/^var\((--theme-[a-z-]+)\)$/)[1]);

            return {id: state.id, label: contrastRatio(text, state.background), bar: contrastRatio(bar, state.background)};
        });

        // Assert: AA text contrast for the label, non-text contrast for the bar
        for (const ratio of ratios) {
            expect(ratio.label, `${ratio.id} label`).toBeGreaterThanOrEqual(4.5);
            expect(ratio.bar, `${ratio.id} bar`).toBeGreaterThanOrEqual(3);
        }
        expect(ratios).toHaveLength(3);
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

    test.each([
        ['', 'inline', 'none'],
        ['dungeon_strip--compact', 'none', 'inline-block'],
        ['dungeon_strip--compact is-open', 'inline', 'none'],
    ])('groupLabel_givenStripState%j_showsTheNameOrTheIcon', (stripState, nameDisplay, iconDisplay) => {
        // Arrange
        renderHeader(`
            <div class="game_version_header"><div class="dungeon_strip ${stripState}" id="strip">
                <div class="dungeon_strip_groups"><div class="dungeon_strip_group">
                    <span class="dungeon_strip_group_label" id="label">
                        <i class="fas fa-dragon dungeon_strip_group_icon" id="icon"></i>
                        <span class="dungeon_strip_group_name" id="name">Raids</span>
                    </span>
                </div></div>
            </div></div>`);

        // Act: jsdom leaves var() unresolved, so a display naming a variable is read from the strip that sets it
        const displayOf = id => {
            const display = getComputedStyle(document.getElementById(id)).display;
            const variable = display.match(/^var\((--[a-z-]+)\)$/);

            return variable === null ? display : getComputedStyle(document.getElementById('strip')).getPropertyValue(variable[1]).trim();
        };

        // Assert: the label itself never disappears, so compact mode keeps a marker between the groups
        expect(getComputedStyle(document.getElementById('label')).display).toBe('block');
        expect(displayOf('name')).toBe(nameDisplay);
        expect(displayOf('icon')).toBe(iconDisplay);
    });
});
