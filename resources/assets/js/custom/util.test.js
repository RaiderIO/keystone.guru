// util.js registers jQuery plugins ($.fn.*) at load time, so a minimal jQuery
// stub must exist before the file is required. As more globals (L, lang,
// getState) are needed for future tests, extend this stub or move it to a
// shared Vitest setup file.
global.$ = {fn: {}};

const {
    convertToSlug,
    slugify,
    toSnakeCase,
    trimEnd,
    getFormattedPercentage,
    isNumeric,
    decodeHtmlEntity,
    isPolygonClockwise,
    getDistance,
    getDistanceSquared,
} = require('./util');

describe('convertToSlug', () => {
    it('converts spaced text to a lowercased, dashed slug', () => {
        expect(convertToSlug('This is a Text')).toBe('this-is-a-text');
    });

    it('strips punctuation and other non-word characters', () => {
        expect(convertToSlug('Hello, World!')).toBe('hello-world');
    });

    it('collapses multiple spaces into a single dash', () => {
        expect(convertToSlug('a    b')).toBe('a-b');
    });
});

describe('slugify', () => {
    it('normalizes diacritics to ASCII', () => {
        expect(slugify('Crème Brûlée')).toBe('creme-brulee');
    });

    it('trims leading and trailing separators', () => {
        expect(slugify('  !Hello! ')).toBe('hello');
    });

    it('returns an empty string for null or undefined', () => {
        expect(slugify(null)).toBe('');
        expect(slugify(undefined)).toBe('');
    });
});

describe('toSnakeCase', () => {
    it('converts PascalCase to snake_case', () => {
        expect(toSnakeCase('CamelCaseKey')).toBe('camel_case_key');
    });

    it('lowercases a single word', () => {
        expect(toSnakeCase('Name')).toBe('name');
    });
});

describe('trimEnd', () => {
    it('removes trailing occurrences of the character', () => {
        expect(trimEnd('path///', '/')).toBe('path');
    });

    it('leaves the string unchanged when there is no trailing character', () => {
        expect(trimEnd('path', '/')).toBe('path');
    });
});

describe('getFormattedPercentage', () => {
    it('rounds the percentage to one decimal', () => {
        expect(getFormattedPercentage(1, 3)).toBe(33.3);
    });

    it('returns zero when the max is zero', () => {
        expect(getFormattedPercentage(5, 0)).toBe(0);
    });
});

describe('isNumeric', () => {
    it('returns true for numeric strings', () => {
        expect(isNumeric('42')).toBe(true);
        expect(isNumeric('3.14')).toBe(true);
    });

    it('returns false for non-numeric strings', () => {
        expect(isNumeric('abc')).toBe(false);
        expect(isNumeric(' ')).toBe(false);
    });

    it('returns false for non-string values', () => {
        expect(isNumeric(42)).toBe(false);
    });
});

describe('decodeHtmlEntity', () => {
    it('decodes HTML entities back to their characters', () => {
        expect(decodeHtmlEntity('a &amp; b &quot;c&quot;')).toBe('a & b "c"');
    });
});

describe('isPolygonClockwise', () => {
    it('returns true for clockwise points', () => {
        const points = [{x: 0, y: 0}, {x: 0, y: 1}, {x: 1, y: 1}, {x: 1, y: 0}];
        expect(isPolygonClockwise(points)).toBe(true);
    });

    it('returns false for counter-clockwise points', () => {
        const points = [{x: 0, y: 0}, {x: 1, y: 0}, {x: 1, y: 1}, {x: 0, y: 1}];
        expect(isPolygonClockwise(points)).toBe(false);
    });
});

describe('getDistance / getDistanceSquared', () => {
    it('returns the squared euclidean distance between two points', () => {
        expect(getDistanceSquared([0, 0], [3, 4])).toBe(25);
    });

    it('returns the euclidean distance between two points', () => {
        expect(getDistance([0, 0], [3, 4])).toBe(5);
    });
});
