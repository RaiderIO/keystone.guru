const {
    randomColor,
    isColorDark,
    getLuminance,
    hexToRgb,
    rgbToHex,
    parseRgba,
    pickHexFromHandlers,
    hsv2rgb,
    rgb2hsv,
    rgb2hex,
    hex2name,
} = require('./colorutil');

describe('randomColor', () => {
    it('returns a six-digit hex color string', () => {
        expect(randomColor()).toMatch(/^#[0-9a-f]{6}$/);
    });
});

describe('hexToRgb', () => {
    it('converts a full-length hex color to an rgb object', () => {
        expect(hexToRgb('#ffffff')).toEqual({r: 255, g: 255, b: 255});
        expect(hexToRgb('#000000')).toEqual({r: 0, g: 0, b: 0});
    });

    it('expands shorthand hex before converting', () => {
        expect(hexToRgb('#03f')).toEqual({r: 0, g: 51, b: 255});
    });

    it('returns null for an invalid hex value', () => {
        expect(hexToRgb('not-a-color')).toBeNull();
    });
});

describe('rgbToHex', () => {
    it('converts an rgb object to a hex string', () => {
        expect(rgbToHex({r: 0, g: 51, b: 255})).toBe('#0033ff');
    });

    it('pads single-digit components with a leading zero', () => {
        expect(rgbToHex({r: 1, g: 2, b: 3})).toBe('#010203');
    });
});

describe('getLuminance', () => {
    it('returns zero for white', () => {
        expect(getLuminance('#ffffff')).toBeCloseTo(0, 5);
    });

    it('returns one for black', () => {
        expect(getLuminance('#000000')).toBeCloseTo(1, 5);
    });

    it('defaults to black for an empty string', () => {
        expect(getLuminance('')).toBeCloseTo(1, 5);
    });
});

describe('isColorDark', () => {
    it('returns true for black', () => {
        expect(isColorDark('#000000')).toBe(true);
    });

    it('returns false for white', () => {
        expect(isColorDark('#ffffff')).toBe(false);
    });
});

describe('rgbToHex', () => {
    it('keeps full-width components unchanged', () => {
        expect(rgbToHex({r: 255, g: 0, b: 128})).toBe('#ff0080');
    });
});

describe('parseRgba', () => {
    it('parses an rgba string into its components', () => {
        expect(parseRgba('rgba(171, 212, 115, 255)')).toEqual({r: 171, g: 212, b: 115, a: 255});
    });

    it('parses components regardless of surrounding spaces', () => {
        expect(parseRgba('rgba(0,0,0,0)')).toEqual({r: 0, g: 0, b: 0, a: 0});
    });

    it('returns NaN for the alpha when it is missing', () => {
        const result = parseRgba('rgba(1, 2, 3)');
        expect(result.r).toBe(1);
        expect(result.a).toBeNaN();
    });
});

describe('hsv2rgb', () => {
    it('returns white for a fully bright, unsaturated color', () => {
        expect(hsv2rgb(0, 0, 1)).toEqual([1, 1, 1]);
    });

    it('returns pure red for hue zero, full saturation and value', () => {
        const [r, g, b] = hsv2rgb(0, 1, 1);
        expect(r).toBeCloseTo(1, 10);
        expect(g).toBeCloseTo(0, 10);
        expect(b).toBeCloseTo(0, 10);
    });

    it('returns mid grey for an unsaturated half-value color', () => {
        expect(hsv2rgb(123, 0, 0.5)).toEqual([0.5, 0.5, 0.5]);
    });
});

describe('rgb2hsv', () => {
    it('returns hue 120 for pure green', () => {
        expect(rgb2hsv(0, 1, 0)).toEqual([120, 1, 1]);
    });

    it('returns all zeroes for black', () => {
        expect(rgb2hsv(0, 0, 0)).toEqual([0, 0, 0]);
    });

    it('round-trips an hsv color through rgb and back', () => {
        const [r, g, b] = hsv2rgb(120, 1, 1);
        const [h, s, v] = rgb2hsv(r, g, b);
        expect(h).toBeCloseTo(120, 10);
        expect(s).toBeCloseTo(1, 10);
        expect(v).toBeCloseTo(1, 10);
    });
});

describe('rgb2hex', () => {
    it('joins rounded components into a hex string', () => {
        expect(rgb2hex(255, 0, 128)).toBe('#ff0080');
    });

    it('rounds fractional components before converting', () => {
        expect(rgb2hex(254.6, 0.4, 127.5)).toBe('#ff0080');
    });

    // Documents a quirk: pad() appends a trailing zero, so single-hex-digit
    // components (values < 16) are scaled up by 16 rather than zero-padded.
    it('appends a trailing zero to single-digit components', () => {
        expect(rgb2hex(5, 5, 5)).toBe('#505050');
    });
});

describe('hex2name', () => {
    it('names primary colors by their hue', () => {
        expect(hex2name('#ff0000')).toBe('red');
        expect(hex2name('#00ff00')).toBe('green');
        expect(hex2name('#0000ff')).toBe('blue');
    });

    it('throws for a shorthand hex value', () => {
        expect(() => hex2name('#fff')).toThrow('Invalid hex value');
    });
});

describe('pickHexFromHandlers', () => {
    it('returns the first color when the weight is before the start', () => {
        const handlers = [[0, '#000000'], [100, '#ffffff']];
        expect(pickHexFromHandlers(handlers, -10)).toBe('#000000');
    });

    it('returns the last color when the weight is after the end', () => {
        const handlers = [[0, '#000000'], [100, '#ffffff']];
        expect(pickHexFromHandlers(handlers, 200)).toBe('#ffffff');
    });

    it('interpolates the color at the midpoint weight', () => {
        const handlers = [[0, '#000000'], [100, '#ffffff']];
        expect(pickHexFromHandlers(handlers, 50)).toBe('#808080');
    });

    it('interpolates within the correct segment of a multi-stop gradient', () => {
        const handlers = [[0, '#ff0000'], [50, '#00ff00'], [100, '#0000ff']];
        expect(pickHexFromHandlers(handlers, 25)).toBe('#808000');
        expect(pickHexFromHandlers(handlers, 75)).toBe('#008080');
    });

    it('returns the exact stop color when the weight lands on a handler', () => {
        const handlers = [[0, '#ff0000'], [50, '#00ff00'], [100, '#0000ff']];
        expect(pickHexFromHandlers(handlers, 50)).toBe('#00ff00');
    });
});
