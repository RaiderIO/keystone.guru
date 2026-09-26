/**
 * @typedef {Object} EnemyCanvasSprite What goes inside an enemy's border: background fills and an optional image.
 * @property backgroundColors {Array.<String|null>} Filled bottom to top; null entries are skipped. The first
 *           fills the whole circle, the others only the circle inside imageInset.
 * @property imageUrl {String|null}
 * @property imageFit {String} 'cover' or 'contain', as CSS background-size.
 * @property blendMode {String} globalCompositeOperation the image is drawn with onto the fills.
 * @property [stateBorder] {{width: Number, color: String, dashed: Boolean}|null} Dangerous, patrol, ... along the
 *           circle's edge. CSS sizes the image inside it, so it also insets the image.
 * @property [text] {{value: String, font: String, color: String}|null} Centred; font includes the size.
 */

/**
 * Pre-renders the image layer of an enemy - circle-clipped fills, the class/type sprite or portrait
 * with its blend mode, the state border and the text - once per (appearance, whole-pixel size), so
 * drawing an enemy per frame is a single unscaled drawImage. Scaling an image and blending it are
 * the expensive parts of drawing an enemy. It also makes the layer one opaque image, so an enemy's
 * opacity is applied to it as a whole, like CSS opacity is, instead of to each overlapping stroke.
 * Badges, which do not scale with the zoom, are pre-rendered once per box style.
 */
class EnemyCanvasSpriteCache {

    /**
     * @param options {Object}
     * @param options.pixelRatio {Number} Device pixels per CSS pixel the sprites are rendered at. Defaults to
     *        what L.Canvas renders at: 2 on any screen above 1x, else 1.
     * @param options.onImageLoaded {function(String)} Called with the url once an image finished loading.
     * @param options.createCanvas {function(Number, Number): HTMLCanvasElement}
     * @param options.createImage {function(): HTMLImageElement}
     */
    constructor(options = {}) {
        this._pixelRatio = options.pixelRatio ?? (typeof L !== 'undefined' && L.Browser.retina ? 2 : 1);
        this._onImageLoaded = options.onImageLoaded ?? (() => {
        });
        this._createCanvas = options.createCanvas ?? EnemyCanvasSpriteCache._createCanvasElement;
        this._createImage = options.createImage ?? (() => new Image());

        /** @type {Map<String, HTMLCanvasElement>} */
        this._sprites = new Map();
        /** @type {Map<String, HTMLCanvasElement>} */
        this._badges = new Map();
        /** @type {WeakMap<Object, String>} */
        this._keys = new WeakMap();
        /** @type {Map<String, {image: HTMLImageElement, state: String}>} */
        this._images = new Map();
    }

    /**
     * @param sprite {EnemyCanvasSprite}
     * @param size {Number} Diameter in CSS pixels; rounded to whole pixels.
     * @returns {HTMLCanvasElement|null} Null while the sprite's image is still loading.
     */
    get(sprite, size) {
        let quantisedSize = EnemyCanvasSpriteCache.quantiseSize(size);
        let key = `${this._getMemoisedKey(sprite, EnemyCanvasSpriteCache.getSpriteKey)}|${quantisedSize}`;

        let cached = this._sprites.get(key);
        if (cached !== undefined) {
            return cached;
        }

        let image = null;
        if (sprite.imageUrl !== null) {
            let entry = this._getImage(sprite.imageUrl);
            if (entry.state === 'loading') {
                return null;
            }

            // A failed image leaves the fills only, like the DOM icon does
            image = entry.state === 'loaded' ? entry.image : null;
        }

        let canvas = this._render(sprite, image, quantisedSize);
        this._sprites.set(key, canvas);

        return canvas;
    }

    /**
     * @param box {EnemyCanvasBoxStyle}
     * @returns {HTMLCanvasElement|null} Null while the box's image is still loading.
     */
    getBadge(box) {
        let key = this._getMemoisedKey(box, JSON.stringify);

        let cached = this._badges.get(key);
        if (cached !== undefined) {
            return cached;
        }

        let image = null;
        if (box.imageUrl !== null) {
            let entry = this._getImage(box.imageUrl);
            if (entry.state === 'loading') {
                return null;
            }

            image = entry.state === 'loaded' ? entry.image : null;
        }

        let canvas = this._renderBadge(box, image);
        this._badges.set(key, canvas);

        return canvas;
    }

    /**
     * Keys are looked up for every enemy on every frame, but the objects they are built from only
     * change when an enemy's appearance does.
     * @param object {Object}
     * @param createKey {function(Object): String}
     * @returns {String}
     * @private
     */
    _getMemoisedKey(object, createKey) {
        let key = this._keys.get(object);
        if (key === undefined) {
            key = createKey(object);
            this._keys.set(object, key);
        }

        return key;
    }

    /**
     * Drops every rendered sprite and badge, e.g. once a web font their text needs has loaded. Loaded
     * images are kept.
     */
    clear() {
        this._sprites.clear();
        this._badges.clear();
    }

    /**
     * @returns {Number}
     */
    getSpriteCount() {
        return this._sprites.size;
    }

    /**
     * @param url {String}
     * @returns {{image: HTMLImageElement, state: String}}
     * @private
     */
    _getImage(url) {
        let entry = this._images.get(url);
        if (entry !== undefined) {
            return entry;
        }

        let image = this._createImage();
        entry = {image: image, state: 'loading'};
        this._images.set(url, entry);

        let self = this;
        image.onload = function () {
            entry.state = 'loaded';
            self._onImageLoaded(url);
        };
        image.onerror = function () {
            entry.state = 'failed';
            self._onImageLoaded(url);
        };
        image.src = url;

        return entry;
    }

    /**
     * @param sprite {EnemyCanvasSprite}
     * @param image {HTMLImageElement|null}
     * @param size {Number} Whole CSS pixels.
     * @returns {HTMLCanvasElement}
     * @private
     */
    _render(sprite, image, size) {
        let pixels = Math.max(1, Math.round(size * this._pixelRatio));
        let canvas = this._createCanvas(pixels, pixels);
        let ctx = canvas.getContext('2d');
        if (ctx === null) {
            return canvas;
        }

        let half = pixels / 2;
        ctx.beginPath();
        ctx.arc(half, half, half, 0, Math.PI * 2);
        ctx.clip();

        if (sprite.backgroundColors.length > 0 && sprite.backgroundColors[0] !== null) {
            ctx.fillStyle = sprite.backgroundColors[0];
            ctx.fillRect(0, 0, pixels, pixels);
        }

        let stateBorder = sprite.stateBorder ?? null;
        let inset = stateBorder === null ? 0 : Math.min(half, stateBorder.width * this._pixelRatio);
        let innerPixels = pixels - inset * 2;

        ctx.save();
        if (inset > 0) {
            ctx.beginPath();
            ctx.arc(half, half, half - inset, 0, Math.PI * 2);
            ctx.clip();
        }

        for (let i = 1; i < sprite.backgroundColors.length; i++) {
            if (sprite.backgroundColors[i] !== null) {
                ctx.fillStyle = sprite.backgroundColors[i];
                ctx.fillRect(0, 0, pixels, pixels);
            }
        }

        if (image !== null) {
            let imageWidth = image.naturalWidth || image.width;
            let imageHeight = image.naturalHeight || image.height;
            if (imageWidth > 0 && imageHeight > 0) {
                let scale = sprite.imageFit === 'contain' ?
                    Math.min(innerPixels / imageWidth, innerPixels / imageHeight) :
                    Math.max(innerPixels / imageWidth, innerPixels / imageHeight);
                let width = imageWidth * scale;
                let height = imageHeight * scale;

                ctx.globalCompositeOperation = sprite.blendMode;
                ctx.drawImage(image, (pixels - width) / 2, (pixels - height) / 2, width, height);
                ctx.globalCompositeOperation = 'source-over';
            }
        }
        ctx.restore();

        if (inset > 0 && stateBorder.color !== null) {
            let radius = half - inset / 2;
            ctx.beginPath();
            ctx.arc(half, half, radius, 0, Math.PI * 2);
            ctx.lineWidth = inset;
            ctx.strokeStyle = stateBorder.color;
            if (stateBorder.dashed) {
                ctx.setLineDash(EnemyCanvasSpriteCache.getDashPattern(Math.PI * 2 * radius, inset));
            }
            ctx.stroke();
            ctx.setLineDash([]);
        }

        let text = sprite.text ?? null;
        if (text !== null) {
            ctx.scale(this._pixelRatio, this._pixelRatio);
            ctx.font = text.font;
            ctx.fillStyle = text.color;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(text.value, size / 2, size / 2);
        }

        return canvas;
    }

    /**
     * Dashes the way Chrome draws a dashed CSS border around a circle: about three border widths per
     * dash and gap, stretched so a whole number of them goes round.
     * @param circumference {Number}
     * @param width {Number} Border width, in the same unit.
     * @returns {Number[]}
     */
    static getDashPattern(circumference, width) {
        let count = Math.max(1, Math.round(circumference / (width * 3)));
        let period = circumference / count;

        return [period * 0.6, period * 0.4];
    }

    /**
     * A box as CSS paints it: rounded background, the background image positioned and sized inside the
     * border (no-repeat), then the border.
     * @param box {EnemyCanvasBoxStyle}
     * @param image {HTMLImageElement|null}
     * @returns {HTMLCanvasElement}
     * @private
     */
    _renderBadge(box, image) {
        let ratio = this._pixelRatio;
        let canvas = this._createCanvas(
            Math.max(1, Math.round(box.width * ratio)),
            Math.max(1, Math.round(box.height * ratio))
        );
        let ctx = canvas.getContext('2d');
        if (ctx === null) {
            return canvas;
        }

        ctx.scale(ratio, ratio);

        let radius = Math.min(box.borderRadius, box.width / 2, box.height / 2);
        ctx.save();
        EnemyCanvasSpriteCache.roundedRect(ctx, 0, 0, box.width, box.height, radius);
        ctx.clip();

        if (box.backgroundColor !== null) {
            ctx.fillStyle = box.backgroundColor;
            ctx.fillRect(0, 0, box.width, box.height);
        }

        let paddingWidth = box.width - box.borderWidth * 2;
        let paddingHeight = box.height - box.borderWidth * 2;
        if (image !== null && paddingWidth > 0 && paddingHeight > 0) {
            let imageWidth = image.naturalWidth || image.width;
            let imageHeight = image.naturalHeight || image.height;
            if (imageWidth > 0 && imageHeight > 0) {
                let size = EnemyCanvasSpriteCache.resolveBackgroundSize(
                    box.imageSize, paddingWidth, paddingHeight, imageWidth, imageHeight
                );
                let position = EnemyCanvasSpriteCache.resolveBackgroundPosition(
                    box.imagePosition, paddingWidth, paddingHeight, size.width, size.height
                );

                ctx.beginPath();
                ctx.rect(box.borderWidth, box.borderWidth, paddingWidth, paddingHeight);
                ctx.clip();
                ctx.drawImage(image, box.borderWidth + position.x, box.borderWidth + position.y, size.width, size.height);
            }
        }
        ctx.restore();

        if (box.borderWidth > 0 && box.borderColor !== null) {
            let half = box.borderWidth / 2;
            EnemyCanvasSpriteCache.roundedRect(
                ctx, half, half, box.width - box.borderWidth, box.height - box.borderWidth, Math.max(0, radius - half)
            );
            ctx.lineWidth = box.borderWidth;
            ctx.strokeStyle = box.borderColor;
            ctx.stroke();
        }

        return canvas;
    }

    /**
     * @param ctx {CanvasRenderingContext2D}
     * @param x {Number}
     * @param y {Number}
     * @param width {Number}
     * @param height {Number}
     * @param radius {Number}
     */
    static roundedRect(ctx, x, y, width, height, radius) {
        ctx.beginPath();
        ctx.moveTo(x + radius, y);
        ctx.arcTo(x + width, y, x + width, y + height, radius);
        ctx.arcTo(x + width, y + height, x, y + height, radius);
        ctx.arcTo(x, y + height, x, y, radius);
        ctx.arcTo(x, y, x + width, y, radius);
        ctx.closePath();
    }

    /**
     * @param cssSize {String} A computed background-size: 'auto', 'contain', 'cover' or one or two lengths.
     * @param areaWidth {Number}
     * @param areaHeight {Number}
     * @param imageWidth {Number}
     * @param imageHeight {Number}
     * @returns {{width: Number, height: Number}}
     */
    static resolveBackgroundSize(cssSize, areaWidth, areaHeight, imageWidth, imageHeight) {
        if (cssSize === 'contain' || cssSize === 'cover') {
            let scale = cssSize === 'contain' ?
                Math.min(areaWidth / imageWidth, areaHeight / imageHeight) :
                Math.max(areaWidth / imageWidth, areaHeight / imageHeight);

            return {width: imageWidth * scale, height: imageHeight * scale};
        }

        let parts = (cssSize || 'auto').trim().split(/\s+/);
        let width = EnemyCanvasSpriteCache._resolveLength(parts[0], areaWidth);
        let height = EnemyCanvasSpriteCache._resolveLength(parts[1] ?? 'auto', areaHeight);

        if (width === null && height === null) {
            return {width: imageWidth, height: imageHeight};
        }

        return {
            width: width ?? imageWidth * (height / imageHeight),
            height: height ?? imageHeight * (width / imageWidth),
        };
    }

    /**
     * @param cssPosition {String} A computed background-position of lengths and percentages, e.g. '-22px 0px'.
     * @param areaWidth {Number}
     * @param areaHeight {Number}
     * @param imageWidth {Number}
     * @param imageHeight {Number}
     * @returns {{x: Number, y: Number}} Offset of the image's top left corner inside the area.
     */
    static resolveBackgroundPosition(cssPosition, areaWidth, areaHeight, imageWidth, imageHeight) {
        let parts = (cssPosition || '0% 0%').split(',')[0].trim().split(/\s+/);

        return {
            x: EnemyCanvasSpriteCache._resolveLength(parts[0], areaWidth - imageWidth) ?? 0,
            y: EnemyCanvasSpriteCache._resolveLength(parts[1] ?? '50%', areaHeight - imageHeight) ?? 0,
        };
    }

    /**
     * @param cssLength {String} A px length, a percentage or 'auto'.
     * @param percentageOf {Number}
     * @returns {Number|null} Null for 'auto' and anything unparseable.
     * @private
     */
    static _resolveLength(cssLength, percentageOf) {
        if (typeof cssLength !== 'string' || cssLength === 'auto') {
            return null;
        }

        let value = parseFloat(cssLength);
        if (isNaN(value)) {
            return null;
        }

        return cssLength.endsWith('%') ? percentageOf * value / 100 : value;
    }

    /**
     * @param size {Number}
     * @returns {Number}
     */
    static quantiseSize(size) {
        return Math.max(1, Math.round(size));
    }

    /**
     * @param sprite {EnemyCanvasSprite}
     * @param size {Number}
     * @returns {String}
     */
    static getKey(sprite, size) {
        return `${EnemyCanvasSpriteCache.getSpriteKey(sprite)}|${EnemyCanvasSpriteCache.quantiseSize(size)}`;
    }

    /**
     * @param sprite {EnemyCanvasSprite}
     * @returns {String} The key of everything but the size.
     */
    static getSpriteKey(sprite) {
        return [
            sprite.imageUrl ?? '',
            sprite.imageFit,
            sprite.blendMode,
            sprite.backgroundColors.join(','),
            JSON.stringify(sprite.stateBorder ?? null),
            JSON.stringify(sprite.text ?? null),
        ].join('|');
    }

    /**
     * @param width {Number}
     * @param height {Number}
     * @returns {HTMLCanvasElement}
     * @private
     */
    static _createCanvasElement(width, height) {
        let canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        return canvas;
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        EnemyCanvasSpriteCache,
    };
}
