const {
    randomColor,
    isColorDark,
    getLuminance,
    hexToRgb,
    rgbToHex,
    parseRgba,
    pickHexFromHandlers,
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

describe('parseRgba', () => {
    it('parses an rgba string into its components', () => {
        expect(parseRgba('rgba(171, 212, 115, 255)')).toEqual({r: 171, g: 212, b: 115, a: 255});
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
});
