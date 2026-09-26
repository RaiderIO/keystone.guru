const {EnemyCanvasStyleProbe} = require('./enemycanvasstyleprobe');

const STYLESHEET = `
    .enemy_icon { background-color: white; background-size: cover; }
    .enemy_icon_npc_class.aggressive { background-color: #dc3c3c; }
    .enemy_icon_npc_enemy_portrait { background-color: black; }
    .enemy_icon_npc_enemy_portrait_inner { background-color: #040C1F; background-size: contain; }
    .melee { background-image: url('https://assets.example/images/enemyclasses/melee.png'); }
    .blended { background-blend-mode: luminosity; }
    .dangerous { border: 3px dashed #ff9900; }
    .inspiring { border: 3px solid #ffd500; }
    .patrol { border: 3px dashed #0017be; }
    .forces_text { color: #ffffff; font: italic 700 12px Arial, sans-serif; }
    .modifier { width: 25px; height: 25px; background-color: white; border: 1px solid black; border-radius: 18px; box-sizing: border-box; }
    .modifier_external { width: 16px; height: 16px; }
    .skull_marker { background-image: url('https://assets.example/raidmarkers.png'); background-position: -66px -22px; }
    .content_box { width: 20px; height: 10px; border: 2px dashed red; box-sizing: content-box; }
`;

let styleElement = null;

beforeEach(() => {
    styleElement = document.createElement('style');
    styleElement.textContent = STYLESHEET;
    document.head.appendChild(styleElement);
});

afterEach(() => {
    styleElement.remove();
    document.body.innerHTML = '';
});

test('read_givenAggressiveClassSprite_returnsColoursAndImageFromStylesheet', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();

    // Act
    const style = probe.read('aggressive enemy_icon_npc_class', 'enemy_icon melee');

    // Assert
    expect(style.outerBackgroundColor).toBe('rgb(220, 60, 60)');
    expect(style.innerBackgroundColor).toBe('rgb(255, 255, 255)');
    expect(style.innerImageUrl).toBe('https://assets.example/images/enemyclasses/melee.png');
    expect(style.innerImageFit).toBe('cover');
    expect(style.innerImageBlendMode).toBe('source-over');
    expect(style.contentBackgroundColor).toBeNull();
});

test('read_givenNoAggressivenessClass_returnsNullOuterBackground', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();

    // Act
    const style = probe.read('enemy_icon_npc_class', 'enemy_icon melee');

    // Assert
    expect(style.outerBackgroundColor).toBeNull();
});

test('read_givenPortraitContentClasses_returnsContentBackgroundAndContainFit', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();

    // Act
    const style = probe.read('enemy_icon_npc_enemy_portrait', 'enemy_icon enemy_portrait', 'enemy_icon_npc_enemy_portrait_inner');

    // Assert
    expect(style.outerBackgroundColor).toBe('rgb(0, 0, 0)');
    expect(style.innerImageUrl).toBeNull();
    expect(style.contentBackgroundColor).toBe('rgb(4, 12, 31)');
    expect(style.contentImageFit).toBe('contain');
});

test('read_givenBlendModeOnImageElement_returnsMatchingCompositeOperation', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();

    // Act
    const style = probe.read('enemy_icon_npc_class', 'enemy_icon melee blended');

    // Assert
    expect(style.innerImageBlendMode).toBe('luminosity');
});

test('read_givenSameClassesTwice_readsComputedStyleOnce', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();
    const spy = vi.spyOn(window, 'getComputedStyle');

    // Act
    const first = probe.read('aggressive enemy_icon_npc_class', 'enemy_icon melee');
    const callsAfterFirstRead = spy.mock.calls.length;
    const second = probe.read('aggressive enemy_icon_npc_class', 'enemy_icon melee');

    // Assert
    expect(second).toBe(first);
    expect(spy.mock.calls.length).toBe(callsAfterFirstRead);
});

test('read_givenSeveralReads_attachesOneHiddenProbeTree', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();

    // Act
    probe.read('enemy_icon_npc_class', 'enemy_icon melee');
    probe.read('aggressive enemy_icon_npc_class', 'enemy_icon melee');

    // Assert
    const roots = document.body.querySelectorAll('[aria-hidden="true"]');
    expect(roots).toHaveLength(1);
    expect(roots[0].style.visibility).toBe('hidden');
});

test.each([
    ['url("https://a/b.png")', 'https://a/b.png'],
    ["url('https://a/b.png')", 'https://a/b.png'],
    ['url(https://a/b.png)', 'https://a/b.png'],
    ['none', null],
    ['', null],
])('parseCssUrl_given%s_returnsUrl', (cssValue, expected) => {
    expect(EnemyCanvasStyleProbe.parseCssUrl(cssValue)).toBe(expected);
});

test.each([
    ['rgba(0, 0, 0, 0)', null],
    ['transparent', null],
    ['rgb(1, 2, 3)', 'rgb(1, 2, 3)'],
    ['rgba(1, 2, 3, 0.5)', 'rgba(1, 2, 3, 0.5)'],
])('toCanvasColor_given%s_returnsColourOrNull', (cssValue, expected) => {
    expect(EnemyCanvasStyleProbe.toCanvasColor(cssValue)).toBe(expected);
});

test.each([
    ['normal', 'source-over'],
    ['luminosity', 'luminosity'],
    ['luminosity, normal', 'luminosity'],
])('toCompositeOperation_given%s_returnsCompositeOperation', (cssValue, expected) => {
    expect(EnemyCanvasStyleProbe.toCompositeOperation(cssValue)).toBe(expected);
});

test('read_givenStateBorderClass_returnsInnerBorder', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();

    // Act
    const style = probe.read('enemy_icon_npc_class', 'enemy_icon melee dangerous');

    // Assert
    expect(style.innerBorderWidth).toBe(3);
    expect(style.innerBorderColor).toBe('rgb(255, 153, 0)');
    expect(style.innerBorderDashed).toBe(true);
});

test('read_givenSolidStateBorder_returnsNotDashed', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();

    // Act
    const style = probe.read('enemy_icon_npc_class', 'enemy_icon melee inspiring');

    // Assert
    expect(style.innerBorderDashed).toBe(false);
    expect(style.innerBorderColor).toBe('rgb(255, 213, 0)');
});

test('read_givenNoStateBorder_returnsZeroBorderWidth', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();

    // Act
    const style = probe.read('enemy_icon_npc_class', 'enemy_icon melee');

    // Assert
    expect(style.innerBorderWidth).toBe(0);
});

test('read_givenPatrolColourInline_returnsInlineColourOverClassColour', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();

    // Act
    const classOnly = probe.read('enemy_icon_npc_class', 'enemy_icon melee patrol');
    const withInline = probe.read('enemy_icon_npc_class', 'enemy_icon melee patrol', '', 'border-color: rgb(0, 50, 128);');

    // Assert
    expect(classOnly.innerBorderColor).toBe('rgb(0, 23, 190)');
    expect(withInline.innerBorderColor).toBe('rgb(0, 50, 128)');
});

test('read_givenInlineStyleThenNone_doesNotKeepInlineStyle', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();
    probe.read('enemy_icon_npc_class', 'enemy_icon melee patrol', '', 'border-color: rgb(0, 50, 128);');

    // Act
    const style = probe.read('enemy_icon_npc_class', 'enemy_icon caster patrol');

    // Assert
    expect(style.innerBorderColor).toBe('rgb(0, 23, 190)');
});

test('read_givenTextClasses_returnsTextColourAndFont', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();

    // Act
    const style = probe.read('enemy_icon_npc_enemy_forces', 'enemy_icon enemy_forces', '', '', 'forces_text');

    // Assert
    expect(style.textColor).toBe('rgb(255, 255, 255)');
    expect(EnemyCanvasStyleProbe.toCanvasFont(style, 14)).toBe('italic 700 14px Arial, sans-serif');
});

test('read_givenNoTextClasses_returnsNoText', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();

    // Act
    const style = probe.read('enemy_icon_npc_class', 'enemy_icon melee');

    // Assert
    expect(style.textColor).toBeNull();
    expect(style.textGlyph).toBeNull();
});

test('read_givenTextElementWithBeforeContent_returnsGlyph', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();
    const getComputedStyle = window.getComputedStyle.bind(window);
    vi.spyOn(window, 'getComputedStyle').mockImplementation((element, pseudo) => pseudo === '::before' ?
        {content: '"\uf057" / ""'} : getComputedStyle(element));

    // Act
    const style = probe.read('enemy_icon_npc_enemy_portrait', 'enemy_icon enemy_portrait', '', '', 'fa fa-times-circle');

    // Assert
    expect(style.textGlyph).toBe('\uf057');
});

test('readBox_givenBadgeClasses_returnsBoxStyle', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();

    // Act
    const box = probe.readBox('modifier skull_marker');

    // Assert
    expect(box.width).toBe(25);
    expect(box.height).toBe(25);
    expect(box.backgroundColor).toBe('rgb(255, 255, 255)');
    expect(box.borderWidth).toBe(1);
    expect(box.borderColor).toBe('rgb(0, 0, 0)');
    expect(box.borderDashed).toBe(false);
    expect(box.borderRadius).toBe(18);
    expect(box.imageUrl).toBe('https://assets.example/raidmarkers.png');
    expect(box.imagePosition).toBe('-66px -22px');
});

test('readBox_givenContentBoxSizing_addsBorderToSize', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();

    // Act
    const box = probe.readBox('content_box');

    // Assert
    expect(box.width).toBe(24);
    expect(box.height).toBe(14);
    expect(box.borderDashed).toBe(true);
});

test('readBox_givenSameClassesTwice_readsComputedStyleOnce', () => {
    // Arrange
    const probe = new EnemyCanvasStyleProbe();
    const spy = vi.spyOn(window, 'getComputedStyle');

    // Act
    const first = probe.readBox('modifier modifier_external');
    const callsAfterFirstRead = spy.mock.calls.length;
    const second = probe.readBox('modifier modifier_external');

    // Assert
    expect(second).toBe(first);
    expect(spy.mock.calls.length).toBe(callsAfterFirstRead);
    expect(first.width).toBe(16);
});

test.each([
    ['"a"', 'a'],
    ["'a'", 'a'],
    ['"\uf057" / ""', '\uf057'],
    ['"a" / "alt"', 'a'],
    ['none', null],
    ['normal', null],
    ['""', null],
])('parseCssContent_given%s_returnsFirstString', (cssValue, expected) => {
    expect(EnemyCanvasStyleProbe.parseCssContent(cssValue)).toBe(expected);
});

test.each([
    ['3px', 'dashed', 3],
    ['3px', 'none', 0],
    ['3px', 'hidden', 0],
    ['', 'solid', 0],
])('toBorderWidth_given%sAnd%s_returns%s', (width, style, expected) => {
    expect(EnemyCanvasStyleProbe.toBorderWidth(width, style)).toBe(expected);
});

test('toCanvasFont_givenNoFamily_fallsBackToSansSerif', () => {
    expect(EnemyCanvasStyleProbe.toCanvasFont({textFontStyle: '', textFontWeight: '', textFontFamily: ''}, 10))
        .toBe('10px sans-serif');
});
