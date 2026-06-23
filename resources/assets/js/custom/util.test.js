// Global stubs ($, L, lang, getState) are provided by the shared Vitest setup
// file (resources/assets/js/test/setup.js), so util.js can be required directly.
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
    getLatLngDistance,
    getLatLngDistanceSquared,
    rotateLatLng,
    getCenteroid,
    getQueryParams,
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

describe('slugify edge cases', () => {
    it('collapses runs of separators and symbols into a single dash', () => {
        expect(slugify('a---b___c   d')).toBe('a-b-c-d');
    });

    it('reduces a string of only symbols to an empty string', () => {
        expect(slugify('!!!@@@###')).toBe('');
    });

    it('strips combining marks left after normalization', () => {
        expect(slugify('Pokémon Go')).toBe('pokemon-go');
    });
});

describe('getLatLngDistance / getLatLngDistanceSquared', () => {
    it('returns the squared distance between two lat/lng points', () => {
        expect(getLatLngDistanceSquared({lat: 0, lng: 0}, {lat: 4, lng: 3})).toBe(25);
    });

    it('returns the distance between two lat/lng points', () => {
        expect(getLatLngDistance({lat: 0, lng: 0}, {lat: 4, lng: 3})).toBe(5);
    });

    it('returns zero for two identical points', () => {
        expect(getLatLngDistance({lat: 7, lng: -2}, {lat: 7, lng: -2})).toBe(0);
    });
});

describe('rotateLatLng', () => {
    it('rotates a point 90 degrees around the center', () => {
        const result = rotateLatLng({lat: 0, lng: 0}, {lat: 0, lng: 1}, 90);
        expect(result.lat).toBeCloseTo(1, 10);
        expect(result.lng).toBeCloseTo(0, 10);
    });

    it('returns the point unchanged for a zero degree rotation', () => {
        const latLng = {lat: 3, lng: 5};
        expect(rotateLatLng({lat: 0, lng: 0}, latLng, 0)).toBe(latLng);
    });

    it('returns the center itself when the point equals the center', () => {
        const result = rotateLatLng({lat: 2, lng: 2}, {lat: 2, lng: 2}, 123);
        expect(result.lat).toBeCloseTo(2, 10);
        expect(result.lng).toBeCloseTo(2, 10);
    });
});

describe('getCenteroid', () => {
    beforeEach(() => {
        global.L = {latLng: (lat, lng) => ({lat, lng})};
    });

    afterEach(() => {
        global.L = {};
    });

    it('returns the average of the provided lat/lng points', () => {
        const result = getCenteroid([[0, 0], [0, 4], [4, 4], [4, 0]]);
        expect(result.lat).toBeCloseTo(2, 10);
        expect(result.lng).toBeCloseTo(2, 10);
    });

    it('returns the point itself for a single-element list', () => {
        const result = getCenteroid([[10, -6]]);
        expect(result.lat).toBeCloseTo(10, 10);
        expect(result.lng).toBeCloseTo(-6, 10);
    });
});

describe('getQueryParams', () => {
    const originalSearch = window.location.search;

    afterEach(() => {
        window.history.replaceState({}, '', `${window.location.pathname}${originalSearch}`);
    });

    it('parses the query string into a key/value object', () => {
        window.history.replaceState({}, '', '/?foo=bar&baz=42');
        expect(getQueryParams()).toEqual({foo: 'bar', baz: '42'});
    });

    it('url-decodes keys and values', () => {
        window.history.replaceState({}, '', '/?na%20me=he%26llo');
        expect(getQueryParams()).toEqual({'na me': 'he&llo'});
    });

    it('returns an empty object when there is no query string', () => {
        window.history.replaceState({}, '', '/');
        expect(getQueryParams()).toEqual({});
    });
});
