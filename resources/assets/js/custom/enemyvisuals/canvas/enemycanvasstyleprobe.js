/**
 * Reads how the stylesheets paint an enemy, so the canvas renderer can draw it without a second copy
 * of those colours and images in JavaScript. A hidden element tree shaped like the DOM enemy icon
 * (outer > inner > content > text) is given the classes the DOM icon would get, and its computed
 * style is read back once per distinct class combination. Badges and the selection halo are read
 * the same way from a standalone box element.
 */
class EnemyCanvasStyleProbe {

    /**
     * @param parentElement {HTMLElement|null} Where the hidden probe tree is attached; defaults to the body.
     */
    constructor(parentElement = null) {
        this._parentElement = parentElement;
        /** @type {Object.<string, EnemyCanvasStyle>} */
        this._cache = {};
        /** @type {Object.<string, EnemyCanvasBoxStyle>} */
        this._boxCache = {};
        /** @type {HTMLElement|null} */
        this._root = null;
        this._outer = null;
        this._inner = null;
        this._content = null;
        this._text = null;
        this._box = null;
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
     * @property contentImageBlendMode {String} A CanvasRenderingContext2D globalCompositeOperation.
     * @property innerBorderWidth {Number} The state border (dangerous, patrol, ...) in CSS pixels; 0 when there is none.
     * @property innerBorderColor {String|null}
     * @property innerBorderDashed {Boolean}
     * @property textColor {String|null}
     * @property textFontStyle {String}
     * @property textFontWeight {String}
     * @property textFontFamily {String}
     * @property textGlyph {String|null} The ::before content of the text element (an icon font glyph), or null.
     */

    /**
     * @typedef {Object} EnemyCanvasBoxStyle A badge or the selection halo.
     * @property width {Number} Border box, in CSS pixels.
     * @property height {Number}
     * @property backgroundColor {String|null}
     * @property borderWidth {Number}
     * @property borderColor {String|null}
     * @property borderDashed {Boolean}
     * @property borderRadius {Number}
     * @property imageUrl {String|null}
     * @property imagePosition {String} Computed background-position, e.g. '-22px 0px' or '50% 50%'.
     * @property imageSize {String} Computed background-size, e.g. 'auto', 'contain' or '16px 16px'.
     */

    /**
     * @param outerClasses {String}
     * @param innerClasses {String}
     * @param contentClasses {String} Classes of the element inside the inner circle; empty when the visual has none.
     * @param innerInlineStyle {String} The inner element's inline style, e.g. a patrol's border colour.
     * @param textClasses {String|null} Classes of the text element inside the content; null when there is no text.
     * @returns {EnemyCanvasStyle}
     */
    read(outerClasses, innerClasses, contentClasses = '', innerInlineStyle = '', textClasses = null) {
        let key = `${outerClasses}|${innerClasses}|${contentClasses}|${innerInlineStyle}|${textClasses}`;
        if (this._cache.hasOwnProperty(key)) {
            return this._cache[key];
        }

        this._ensureProbeElements();

        this._outer.className = `outer ${outerClasses}`;
        this._inner.className = `inner ${innerClasses}`;
        this._inner.style.cssText = innerInlineStyle;
        this._content.className = contentClasses;
        this._text.className = textClasses ?? '';

        let outerStyle = window.getComputedStyle(this._outer);
        let innerStyle = window.getComputedStyle(this._inner);
        let contentStyle = window.getComputedStyle(this._content);
        let textStyle = textClasses === null ? null : window.getComputedStyle(this._text);

        let result = {
            outerBackgroundColor: EnemyCanvasStyleProbe.toCanvasColor(outerStyle.backgroundColor),
            innerBackgroundColor: EnemyCanvasStyleProbe.toCanvasColor(innerStyle.backgroundColor),
            innerImageUrl: EnemyCanvasStyleProbe.parseCssUrl(innerStyle.backgroundImage),
            innerImageFit: EnemyCanvasStyleProbe.toImageFit(innerStyle.backgroundSize),
            innerImageBlendMode: EnemyCanvasStyleProbe.toCompositeOperation(innerStyle.backgroundBlendMode),
            contentBackgroundColor: contentClasses === '' ?
                null : EnemyCanvasStyleProbe.toCanvasColor(contentStyle.backgroundColor),
            contentImageFit: EnemyCanvasStyleProbe.toImageFit(contentStyle.backgroundSize),
            contentImageBlendMode: EnemyCanvasStyleProbe.toCompositeOperation(contentStyle.backgroundBlendMode),
            innerBorderWidth: EnemyCanvasStyleProbe.toBorderWidth(innerStyle.borderTopWidth, innerStyle.borderTopStyle),
            innerBorderColor: EnemyCanvasStyleProbe.toCanvasColor(innerStyle.borderTopColor),
            innerBorderDashed: innerStyle.borderTopStyle === 'dashed' || innerStyle.borderTopStyle === 'dotted',
            textColor: textStyle === null ? null : EnemyCanvasStyleProbe.toCanvasColor(textStyle.color),
            textFontStyle: textStyle === null ? '' : textStyle.fontStyle,
            textFontWeight: textStyle === null ? '' : textStyle.fontWeight,
            textFontFamily: textStyle === null ? '' : textStyle.fontFamily,
            textGlyph: textStyle === null ? null :
                EnemyCanvasStyleProbe.parseCssContent(window.getComputedStyle(this._text, '::before').content),
        };

        this._cache[key] = result;

        return result;
    }

    /**
     * @param classes {String} Every class of the box, e.g. 'modifier modifier_external truesight'.
     * @returns {EnemyCanvasBoxStyle}
     */
    readBox(classes) {
        if (this._boxCache.hasOwnProperty(classes)) {
            return this._boxCache[classes];
        }

        this._ensureProbeElements();

        this._box.className = classes;

        let style = window.getComputedStyle(this._box);
        let borderWidth = EnemyCanvasStyleProbe.toBorderWidth(style.borderTopWidth, style.borderTopStyle);
        let borderBoxGrowth = style.boxSizing === 'border-box' ? 0 : borderWidth * 2;
        let result = {
            width: (parseFloat(style.width) || 0) + borderBoxGrowth,
            height: (parseFloat(style.height) || 0) + borderBoxGrowth,
            backgroundColor: EnemyCanvasStyleProbe.toCanvasColor(style.backgroundColor),
            borderWidth: borderWidth,
            borderColor: EnemyCanvasStyleProbe.toCanvasColor(style.borderTopColor),
            borderDashed: style.borderTopStyle === 'dashed' || style.borderTopStyle === 'dotted',
            borderRadius: parseFloat(style.borderTopLeftRadius) || parseFloat(style.borderRadius) || 0,
            imageUrl: EnemyCanvasStyleProbe.parseCssUrl(style.backgroundImage),
            imagePosition: style.backgroundPosition || '0% 0%',
            imageSize: style.backgroundSize || 'auto',
        };

        this._boxCache[classes] = result;

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
        this._text = document.createElement('div');
        this._box = document.createElement('div');

        this._content.appendChild(this._text);
        this._inner.appendChild(this._content);
        this._outer.appendChild(this._inner);
        this._root.appendChild(this._outer);
        this._root.appendChild(this._box);

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
     * @param style {EnemyCanvasStyle}
     * @param size {Number} In CSS pixels.
     * @returns {String} A CanvasRenderingContext2D font for the style's text at that size.
     */
    static toCanvasFont(style, size) {
        return [style.textFontStyle, style.textFontWeight, `${size}px`, style.textFontFamily || 'sans-serif']
            .filter(part => part !== '')
            .join(' ');
    }

    /**
     * @param cssContent {String} A computed ::before content value, e.g. '"\\f057"', 'none' or 'normal'.
     * @returns {String|null}
     */
    static parseCssContent(cssContent) {
        if (typeof cssContent !== 'string') {
            return null;
        }

        // Everything after the first string is alternative text, e.g. '"\\f057" / ""'
        let match = cssContent.match(/^(['"])((?:\\.|(?!\1).)+)\1/);

        return match === null ? null : match[2];
    }

    /**
     * @param cssWidth {String} A computed border width, e.g. '3px'.
     * @param cssStyle {String} A computed border style; 'none' and 'hidden' draw no border at any width.
     * @returns {Number}
     */
    static toBorderWidth(cssWidth, cssStyle) {
        if (cssStyle === 'none' || cssStyle === 'hidden') {
            return 0;
        }

        return parseFloat(cssWidth) || 0;
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
