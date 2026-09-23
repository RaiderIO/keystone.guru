const {EnemyCanvasStyleProbe} = require('./enemycanvasstyleprobe');

const STYLESHEET = `
    .enemy_icon { background-color: white; background-size: cover; }
    .enemy_icon_npc_class.aggressive { background-color: #dc3c3c; }
    .enemy_icon_npc_enemy_portrait { background-color: black; }
    .enemy_icon_npc_enemy_portrait_inner { background-color: #040C1F; background-size: contain; }
    .melee { background-image: url('https://assets.example/images/enemyclasses/melee.png'); }
    .blended { background-blend-mode: luminosity; }
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
