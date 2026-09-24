const {EnemyCanvasSpriteCache} = require('./enemycanvasspritecache');

/**
 * jsdom has no 2D canvas, so the cache is given a canvas factory that records the drawing calls.
 */
function makeFakeCanvasFactory() {
    const created = [];
    const factory = (width, height) => {
        const calls = [];
        const ctx = {
            calls,
            beginPath: () => calls.push(['beginPath']),
            arc: (...args) => calls.push(['arc', ...args]),
            clip: () => calls.push(['clip']),
            fillRect: (...args) => calls.push(['fillRect', ctx.fillStyle, ...args]),
            drawImage: (...args) => calls.push(['drawImage', ctx.globalCompositeOperation, ...args.slice(1)]),
            save: () => calls.push(['save']),
            restore: () => calls.push(['restore']),
            scale: (...args) => calls.push(['scale', ...args]),
            rect: (...args) => calls.push(['rect', ...args]),
            moveTo: (...args) => calls.push(['moveTo', ...args]),
            arcTo: (...args) => calls.push(['arcTo', ...args]),
            closePath: () => calls.push(['closePath']),
            setLineDash: (pattern) => calls.push(['setLineDash', pattern]),
            stroke: () => calls.push(['stroke', ctx.strokeStyle, ctx.lineWidth]),
            fillText: (...args) => calls.push(['fillText', ctx.font, ctx.fillStyle, ...args]),
            fillStyle: null,
            strokeStyle: null,
            lineWidth: 1,
            font: '',
            globalCompositeOperation: 'source-over',
        };
        const canvas = {width, height, ctx, getContext: () => ctx};
        created.push(canvas);

        return canvas;
    };

    return {created, factory};
}

function makeFakeImageFactory() {
    const created = [];
    const factory = () => {
        const image = {naturalWidth: 64, naturalHeight: 32, src: null, onload: null, onerror: null};
        created.push(image);

        return image;
    };

    return {created, factory};
}

function makeSprite(overrides = {}) {
    return {
        backgroundColors: ['rgb(255, 255, 255)'],
        imageUrl: 'https://assets.example/melee.png',
        imageFit: 'cover',
        blendMode: 'source-over',
        ...overrides,
    };
}

function makeCache(overrides = {}) {
    const canvases = makeFakeCanvasFactory();
    const images = makeFakeImageFactory();
    const loaded = [];
    const cache = new EnemyCanvasSpriteCache({
        pixelRatio: 1,
        createCanvas: canvases.factory,
        createImage: images.factory,
        onImageLoaded: (url) => loaded.push(url),
        ...overrides,
    });

    return {cache, canvases, images, loaded};
}

test.each([
    [15.4, 15],
    [15.5, 16],
    [15.6, 16],
    [0.2, 1],
])('quantiseSize_given%s_returns%s', (size, expected) => {
    expect(EnemyCanvasSpriteCache.quantiseSize(size)).toBe(expected);
});

test('getKey_givenSizesRoundingToSamePixel_returnsSameKey', () => {
    const sprite = makeSprite();

    expect(EnemyCanvasSpriteCache.getKey(sprite, 20.3)).toBe(EnemyCanvasSpriteCache.getKey(sprite, 19.7));
});

test.each([
    ['imageUrl', {imageUrl: 'https://assets.example/caster.png'}],
    ['imageFit', {imageFit: 'contain'}],
    ['blendMode', {blendMode: 'luminosity'}],
    ['backgroundColors', {backgroundColors: ['rgb(0, 0, 0)']}],
    ['stateBorder', {stateBorder: {width: 3, color: 'rgb(255, 153, 0)', dashed: true}}],
    ['text', {text: {value: '4', font: '12px Arial', color: 'rgb(255, 255, 255)'}}],
])('getKey_givenDifferent%s_returnsDifferentKey', (label, overrides) => {
    expect(EnemyCanvasSpriteCache.getKey(makeSprite(overrides), 20))
        .not.toBe(EnemyCanvasSpriteCache.getKey(makeSprite(), 20));
});

test('get_givenImageStillLoading_returnsNullAndStartsOneLoad', () => {
    // Arrange
    const {cache, images} = makeCache();

    // Act
    const first = cache.get(makeSprite(), 20);
    const second = cache.get(makeSprite(), 30);

    // Assert
    expect(first).toBeNull();
    expect(second).toBeNull();
    expect(images.created).toHaveLength(1);
    expect(images.created[0].src).toBe('https://assets.example/melee.png');
});

test('get_givenImageLoaded_notifiesAndRendersSprite', () => {
    // Arrange
    const {cache, images, loaded} = makeCache();
    cache.get(makeSprite(), 20);

    // Act
    images.created[0].onload();
    const sprite = cache.get(makeSprite(), 20);

    // Assert
    expect(loaded).toEqual(['https://assets.example/melee.png']);
    expect(sprite).not.toBeNull();
    expect(sprite.width).toBe(20);
});

test('get_givenSameImageAndSizesRoundingToSamePixel_rendersOnce', () => {
    // Arrange
    const {cache, images, canvases} = makeCache();
    cache.get(makeSprite(), 20);
    images.created[0].onload();

    // Act
    const first = cache.get(makeSprite(), 19.8);
    const second = cache.get(makeSprite(), 20.2);

    // Assert
    expect(second).toBe(first);
    expect(canvases.created).toHaveLength(1);
    expect(cache.getSpriteCount()).toBe(1);
});

test('get_givenManyFractionalSizesWithinOnePixelRange_staysBounded', () => {
    // Arrange
    const {cache, images} = makeCache();
    cache.get(makeSprite(), 20);
    images.created[0].onload();

    // Act
    for (let size = 18; size <= 22; size += 0.01) {
        cache.get(makeSprite(), size);
    }

    // Assert - 18 through 22 inclusive
    expect(cache.getSpriteCount()).toBe(5);
});

test('get_givenPixelRatio_rendersAtDevicePixels', () => {
    // Arrange
    const {cache, images} = makeCache({pixelRatio: 2});
    cache.get(makeSprite(), 20);
    images.created[0].onload();

    // Act
    const sprite = cache.get(makeSprite(), 20);

    // Assert
    expect(sprite.width).toBe(40);
    expect(sprite.height).toBe(40);
});

test('get_givenCoverFit_scalesImageToFillAndBakesBlendMode', () => {
    // Arrange
    const {cache, images} = makeCache();
    cache.get(makeSprite({blendMode: 'luminosity'}), 20);
    images.created[0].onload();

    // Act
    const sprite = cache.get(makeSprite({blendMode: 'luminosity'}), 20);

    // Assert - a 64x32 image covering a 20px circle is scaled by 20/32 to 40x20, centred
    const drawCall = sprite.ctx.calls.find((call) => call[0] === 'drawImage');
    expect(drawCall).toEqual(['drawImage', 'luminosity', -10, 0, 40, 20]);
    expect(sprite.ctx.globalCompositeOperation).toBe('source-over');
});

test('get_givenContainFit_scalesImageToFitInside', () => {
    // Arrange
    const {cache, images} = makeCache();
    cache.get(makeSprite({imageFit: 'contain'}), 20);
    images.created[0].onload();

    // Act
    const sprite = cache.get(makeSprite({imageFit: 'contain'}), 20);

    // Assert - scaled by 20/64 to 20x10, centred vertically
    const drawCall = sprite.ctx.calls.find((call) => call[0] === 'drawImage');
    expect(drawCall).toEqual(['drawImage', 'source-over', 0, 5, 20, 10]);
});

test('get_givenBackgroundColors_fillsNonNullColoursInOrder', () => {
    // Arrange
    const {cache} = makeCache();

    // Act
    const sprite = cache.get(makeSprite({imageUrl: null, backgroundColors: ['rgb(0, 0, 0)', null, 'rgb(4, 12, 31)']}), 10);

    // Assert
    const fills = sprite.ctx.calls.filter((call) => call[0] === 'fillRect').map((call) => call[1]);
    expect(fills).toEqual(['rgb(0, 0, 0)', 'rgb(4, 12, 31)']);
    expect(sprite.ctx.calls.some((call) => call[0] === 'drawImage')).toBe(false);
});

test('get_givenImageFailedToLoad_rendersFillsOnly', () => {
    // Arrange
    const {cache, images, loaded} = makeCache();
    cache.get(makeSprite(), 20);

    // Act
    images.created[0].onerror();
    const sprite = cache.get(makeSprite(), 20);

    // Assert
    expect(loaded).toEqual(['https://assets.example/melee.png']);
    expect(sprite).not.toBeNull();
    expect(sprite.ctx.calls.some((call) => call[0] === 'drawImage')).toBe(false);
    expect(sprite.ctx.calls.some((call) => call[0] === 'clip')).toBe(true);
});

function makeBox(overrides = {}) {
    return {
        width: 16,
        height: 16,
        backgroundColor: 'rgb(255, 255, 255)',
        borderWidth: 1,
        borderColor: 'rgb(0, 0, 0)',
        borderDashed: false,
        borderRadius: 18,
        imageUrl: 'https://assets.example/truesight.png',
        imagePosition: '50% 50%',
        imageSize: 'auto',
        ...overrides,
    };
}

test('get_givenStateBorder_insetsImageAndStrokesBorderAlongEdge', () => {
    // Arrange
    const {cache, canvases, images} = makeCache();
    const sprite = makeSprite({imageUrl: null, stateBorder: {width: 3, color: 'rgb(255, 153, 0)', dashed: false}});

    // Act
    cache.get(sprite, 20);

    // Assert
    const calls = canvases.created[0].ctx.calls;
    expect(calls).toContainEqual(['arc', 10, 10, 7, 0, Math.PI * 2]);
    expect(calls).toContainEqual(['arc', 10, 10, 8.5, 0, Math.PI * 2]);
    expect(calls).toContainEqual(['stroke', 'rgb(255, 153, 0)', 3]);
    expect(images.created).toHaveLength(0);
});

test('get_givenStateBorder_drawsImageInsideIt', () => {
    // Arrange
    const {cache, canvases, images} = makeCache();
    const sprite = makeSprite({imageFit: 'contain', stateBorder: {width: 2, color: 'rgb(0, 0, 0)', dashed: false}});
    cache.get(sprite, 20);
    images.created[0].onload();

    // Act
    cache.get(sprite, 20);

    // Assert: a 64x32 image contained in the 16px circle inside the border
    const drawImage = canvases.created[0].ctx.calls.find(call => call[0] === 'drawImage');
    expect(drawImage).toEqual(['drawImage', 'source-over', 2, 6, 16, 8]);
});

test('get_givenSecondBackgroundColour_fillsItOnlyInsideStateBorder', () => {
    // Arrange
    const {cache, canvases} = makeCache();
    const sprite = makeSprite({
        imageUrl: null,
        backgroundColors: ['rgb(0, 0, 0)', 'rgb(4, 12, 31)'],
        stateBorder: {width: 3, color: 'rgb(0, 50, 128)', dashed: true},
    });

    // Act
    cache.get(sprite, 20);

    // Assert
    const calls = canvases.created[0].ctx.calls.map(call => call[0] === 'fillRect' ? `fill ${call[1]}` : call[0]);
    expect(calls.indexOf('fill rgb(0, 0, 0)')).toBeLessThan(calls.lastIndexOf('clip'));
    expect(calls.indexOf('fill rgb(4, 12, 31)')).toBeGreaterThan(calls.lastIndexOf('clip'));
});

test('get_givenDashedStateBorder_dashesWholeNumberOfTimesAround', () => {
    // Arrange
    const {cache, canvases} = makeCache();
    const sprite = makeSprite({imageUrl: null, stateBorder: {width: 3, color: 'rgb(255, 153, 0)', dashed: true}});

    // Act
    cache.get(sprite, 40);

    // Assert
    const dash = canvases.created[0].ctx.calls.find(call => call[0] === 'setLineDash' && call[1].length === 2)[1];
    const circumference = Math.PI * 2 * 18.5;
    const repeats = circumference / (dash[0] + dash[1]);
    expect(repeats).toBeCloseTo(Math.round(repeats), 6);
});

test('get_givenText_drawsItCentredAtPixelRatio', () => {
    // Arrange
    const {cache, canvases} = makeCache({pixelRatio: 2});
    const sprite = makeSprite({imageUrl: null, text: {value: '12', font: '14px Arial', color: 'rgb(255, 255, 255)'}});

    // Act
    cache.get(sprite, 20);

    // Assert
    const calls = canvases.created[0].ctx.calls;
    expect(calls).toContainEqual(['scale', 2, 2]);
    expect(calls).toContainEqual(['fillText', '14px Arial', 'rgb(255, 255, 255)', '12', 10, 10]);
});

test('clear_givenRenderedSprite_rendersItAgainOnNextGet', () => {
    // Arrange
    const {cache, canvases} = makeCache();
    const sprite = makeSprite({imageUrl: null});
    cache.get(sprite, 20);

    // Act
    cache.clear();
    cache.get(sprite, 20);

    // Assert
    expect(canvases.created).toHaveLength(2);
    expect(cache.getSpriteCount()).toBe(1);
});

test.each([
    [2 * Math.PI * 20, 3],
    [2 * Math.PI * 7.5, 3],
    [50, 4],
])('getDashPattern_givenCircumference%sAndWidth%s_repeatsWholeNumberOfTimes', (circumference, width) => {
    const [dash, gap] = EnemyCanvasSpriteCache.getDashPattern(circumference, width);

    const repeats = circumference / (dash + gap);
    expect(repeats).toBeCloseTo(Math.round(repeats), 6);
    expect(dash).toBeGreaterThan(gap);
});

test('getBadge_givenImageStillLoading_returnsNull', () => {
    // Arrange
    const {cache} = makeCache();

    // Act
    const badge = cache.getBadge(makeBox());

    // Assert
    expect(badge).toBeNull();
});

test('getBadge_givenLoadedImage_rendersOnceAtBoxSizeTimesPixelRatio', () => {
    // Arrange
    const {cache, canvases, images} = makeCache({pixelRatio: 2});
    cache.getBadge(makeBox());
    images.created[0].onload();

    // Act
    const first = cache.getBadge(makeBox());
    const second = cache.getBadge(makeBox());

    // Assert
    expect(second).toBe(first);
    expect(canvases.created).toHaveLength(1);
    expect([first.width, first.height]).toEqual([32, 32]);
});

test('getBadge_givenSpriteSheetPosition_drawsImageOffsetInsideBorder', () => {
    // Arrange
    const {cache, canvases, images} = makeCache();
    const box = makeBox({width: 24, height: 24, imagePosition: '-22px -22px'});
    cache.getBadge(box);
    images.created[0].onload();

    // Act
    cache.getBadge(box);

    // Assert
    const calls = canvases.created[0].ctx.calls;
    expect(calls).toContainEqual(['drawImage', 'source-over', -21, -21, 64, 32]);
    expect(calls).toContainEqual(['stroke', 'rgb(0, 0, 0)', 1]);
});

test('getBadge_givenFailedImage_rendersBoxWithoutImage', () => {
    // Arrange
    const {cache, canvases, images} = makeCache();
    cache.getBadge(makeBox());
    images.created[0].onerror();

    // Act
    const badge = cache.getBadge(makeBox());

    // Assert
    expect(badge).not.toBeNull();
    expect(canvases.created[0].ctx.calls.some(call => call[0] === 'drawImage')).toBe(false);
});

test.each([
    ['auto', {width: 64, height: 32}],
    ['auto auto', {width: 64, height: 32}],
    ['contain', {width: 16, height: 8}],
    ['cover', {width: 32, height: 16}],
    ['32px auto', {width: 32, height: 16}],
    ['50%', {width: 8, height: 4}],
    ['10px 20px', {width: 10, height: 20}],
])('resolveBackgroundSize_given%s_returnsDrawnSize', (cssSize, expected) => {
    expect(EnemyCanvasSpriteCache.resolveBackgroundSize(cssSize, 16, 16, 64, 32)).toEqual(expected);
});

test.each([
    ['50% 50%', {x: -24, y: -8}],
    ['0% 0%', {x: 0, y: 0}],
    ['-22px 4px', {x: -22, y: 4}],
    ['100% 0%', {x: -48, y: 0}],
])('resolveBackgroundPosition_given%s_returnsOffset', (cssPosition, expected) => {
    const offset = EnemyCanvasSpriteCache.resolveBackgroundPosition(cssPosition, 16, 16, 64, 32);

    // + 0 turns a 0% offset's -0 into 0
    expect({x: offset.x + 0, y: offset.y + 0}).toEqual(expected);
});
