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
            fillStyle: null,
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
