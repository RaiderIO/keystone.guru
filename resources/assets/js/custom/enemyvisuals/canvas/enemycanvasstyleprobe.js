/**
 * Reads how the stylesheets paint an enemy, so the canvas renderer can draw it without a second copy
 * of those colours and images in JavaScript. A hidden element tree shaped like the DOM enemy icon
 * (outer > inner > content) is given the classes the DOM icon would get, and its computed style is
 * read back once per distinct class combination.
 */
class EnemyCanvasStyleProbe {

    /**
     * @param parentElement {HTMLElement|null} Where the hidden probe tree is attached; defaults to the body.
     */
    constructor(parentElement = null) {
        this._parentElement = parentElement;
        /** @type {Object.<string, EnemyCanvasStyle>} */
        this._cache = {};
        /** @type {HTMLElement|null} */
        this._root = null;
        this._outer = null;
        this._inner = null;
        this._content = null;
    }

    /**
     * @typedef {Object} EnemyCanvasStyle
     * @property outerBackgroundColor {String|null} Null when transparent.
     * @property innerBackgroundColor {String|null} Null when transparent.
     * @property innerImageUrl {String|null}
     * @property innerImageFit {String} 'cover' or 'contain'.
     * @property innerImageBlendMode {String} A CanvasRenderingContext2D globalCompositeOperation.
     * @property contentBackgroundColor {String|null} Null when transparent or when there is no content element.
     * @property contentImageFit {String} 'cover' or 'contain'.
     */

    /**
     * @param outerClasses {String}
     * @param innerClasses {String}
     * @param contentClasses {String} Classes of the element inside the inner circle; empty when the visual has none.
     * @returns {EnemyCanvasStyle}
     */
    read(outerClasses, innerClasses, contentClasses = '') {
        let key = `${outerClasses}|${innerClasses}|${contentClasses}`;
        if (this._cache.hasOwnProperty(key)) {
            return this._cache[key];
        }

        this._ensureProbeElements();

        this._outer.className = `outer ${outerClasses}`;
        this._inner.className = `inner ${innerClasses}`;
        this._content.className = contentClasses;

        let outerStyle = window.getComputedStyle(this._outer);
        let innerStyle = window.getComputedStyle(this._inner);
        let contentStyle = window.getComputedStyle(this._content);

        let result = {
            outerBackgroundColor: EnemyCanvasStyleProbe.toCanvasColor(outerStyle.backgroundColor),
            innerBackgroundColor: EnemyCanvasStyleProbe.toCanvasColor(innerStyle.backgroundColor),
            innerImageUrl: EnemyCanvasStyleProbe.parseCssUrl(innerStyle.backgroundImage),
            innerImageFit: EnemyCanvasStyleProbe.toImageFit(innerStyle.backgroundSize),
            innerImageBlendMode: EnemyCanvasStyleProbe.toCompositeOperation(innerStyle.backgroundBlendMode),
            contentBackgroundColor: contentClasses === '' ?
                null : EnemyCanvasStyleProbe.toCanvasColor(contentStyle.backgroundColor),
            contentImageFit: EnemyCanvasStyleProbe.toImageFit(contentStyle.backgroundSize),
        };

        this._cache[key] = result;

        return result;
    }

    /**
     * @private
     */
    _ensureProbeElements() {
        if (this._root !== null) {
            return;
        }

        this._root = document.createElement('div');
        this._root.setAttribute('aria-hidden', 'true');
        this._root.style.cssText = 'position: absolute; left: -10000px; top: -10000px; visibility: hidden; pointer-events: none;';

        this._outer = document.createElement('div');
        this._inner = document.createElement('div');
        this._content = document.createElement('div');

        this._inner.appendChild(this._content);
        this._outer.appendChild(this._inner);
        this._root.appendChild(this._outer);

        (this._parentElement ?? document.body).appendChild(this._root);
    }

    /**
     * @param cssBackgroundImage {String} A computed background-image value, e.g. url("https://.../melee.png").
     * @returns {String|null}
     */
    static parseCssUrl(cssBackgroundImage) {
        if (typeof cssBackgroundImage !== 'string') {
            return null;
        }

        let match = cssBackgroundImage.match(/url\(\s*(['"]?)(.*?)\1\s*\)/);

        return match === null || match[2] === '' ? null : match[2];
    }

    /**
     * @param cssColor {String} A computed colour, e.g. rgb(220, 60, 60) or rgba(0, 0, 0, 0).
     * @returns {String|null} The colour, or null when it is fully transparent (nothing to fill).
     */
    static toCanvasColor(cssColor) {
        if (typeof cssColor !== 'string' || cssColor === '' || cssColor === 'transparent') {
            return null;
        }

        let match = cssColor.replace(/\s+/g, '').match(/^rgba\(\d+,\d+,\d+,([\d.]+)\)$/);
        if (match !== null && parseFloat(match[1]) === 0) {
            return null;
        }

        return cssColor;
    }

    /**
     * @param cssBackgroundSize {String}
     * @returns {String} 'contain' or 'cover'.
     */
    static toImageFit(cssBackgroundSize) {
        return cssBackgroundSize === 'contain' ? 'contain' : 'cover';
    }

    /**
     * CSS blend modes and canvas composite operations share their names, except CSS's 'normal'.
     * @param cssBlendMode {String}
     * @returns {String}
     */
    static toCompositeOperation(cssBlendMode) {
        if (typeof cssBlendMode !== 'string' || cssBlendMode === '' || cssBlendMode === 'normal') {
            return 'source-over';
        }

        // Several background layers produce a comma separated list; the image is the first layer
        return cssBlendMode.split(',')[0].trim();
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        EnemyCanvasStyleProbe,
    };
}
