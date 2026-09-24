/**
 * @typedef {Object} EnemyCanvasSprite What goes inside an enemy's border: background fills and an optional image.
 * @property backgroundColors {Array.<String|null>} Filled bottom to top; null entries are skipped.
 * @property imageUrl {String|null}
 * @property imageFit {String} 'cover' or 'contain', as CSS background-size.
 * @property blendMode {String} globalCompositeOperation the image is drawn with onto the fills.
 */

/**
 * Pre-renders the image layer of an enemy - circle-clipped fills plus the class/type sprite or
 * portrait, blend mode applied - once per (image, whole-pixel size), so drawing an enemy per frame
 * is a single unscaled drawImage. Scaling an image and blending it are the expensive parts of
 * drawing an enemy; everything around it (fills, strokes) is drawn live.
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
        let key = EnemyCanvasSpriteCache.getKey(sprite, quantisedSize);

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

        for (let i = 0; i < sprite.backgroundColors.length; i++) {
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
                    Math.min(pixels / imageWidth, pixels / imageHeight) :
                    Math.max(pixels / imageWidth, pixels / imageHeight);
                let width = imageWidth * scale;
                let height = imageHeight * scale;

                ctx.globalCompositeOperation = sprite.blendMode;
                ctx.drawImage(image, (pixels - width) / 2, (pixels - height) / 2, width, height);
                ctx.globalCompositeOperation = 'source-over';
            }
        }

        return canvas;
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
        return [
            sprite.imageUrl ?? '',
            sprite.imageFit,
            sprite.blendMode,
            sprite.backgroundColors.join(','),
            EnemyCanvasSpriteCache.quantiseSize(size),
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
