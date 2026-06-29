// Global stubs ($, L, lang, getState) are provided by the shared Vitest setup
// file (resources/assets/js/test/setup.js), so constants.js can be required
// directly. The key-scaling helpers below are pure: they only read the static
// `c.gameData` factors and the affix constants.
const {
    c,
    polylineDefaultColor,
    AFFIX_FORTIFIED,
    AFFIX_TYRANNICAL,
    AFFIX_THUNDERING,
    AFFIX_XALATATHS_GUILE,
} = require('./constants');

describe('c.map.enemy.getKeyScalingFactor', () => {
    it('returns 1 at the lowest key level with no affixes', () => {
        expect(c.map.enemy.getKeyScalingFactor(1, [])).toBe(1);
    });

    it('compounds the base scaling factor per key level', () => {
        expect(c.map.enemy.getKeyScalingFactor(2, [])).toBe(1.1);
        expect(c.map.enemy.getKeyScalingFactor(10, [])).toBe(2.36);
    });

    it('applies the fortified and tyrannical multipliers', () => {
        expect(c.map.enemy.getKeyScalingFactor(1, [AFFIX_FORTIFIED])).toBe(1.2);
        expect(c.map.enemy.getKeyScalingFactor(1, [AFFIX_TYRANNICAL])).toBe(1.3);
        expect(c.map.enemy.getKeyScalingFactor(1, [AFFIX_THUNDERING])).toBe(1.05);
    });

    it('only applies Xalatath\'s Guile from key level 12 upwards', () => {
        expect(c.map.enemy.getKeyScalingFactor(11, [AFFIX_XALATATHS_GUILE])).toBe(2.59);
        expect(c.map.enemy.getKeyScalingFactor(12, [AFFIX_XALATATHS_GUILE])).toBe(3.71);
    });

    it('returns 1 for a weird, sub-minimum key level', () => {
        expect(c.map.enemy.getKeyScalingFactor(0, [])).toBe(1);
    });
});

describe('c.map.enemy.calculateHealthForKey / calculateBaseHealthForKey', () => {
    it('scales base health up for a higher key', () => {
        expect(c.map.enemy.calculateHealthForKey(100, 2, [])).toBe(110);
    });

    it('recovers the base health by scaling back down', () => {
        expect(c.map.enemy.calculateBaseHealthForKey(110, 2, [])).toBe(100);
    });

    it('round-trips base health through scaling with affixes', () => {
        const base = 1000;
        const scaled = c.map.enemy.calculateHealthForKey(base, 5, [AFFIX_FORTIFIED]);
        expect(c.map.enemy.calculateBaseHealthForKey(scaled, 5, [AFFIX_FORTIFIED])).toBe(base);
    });

    it('leaves health unchanged at the lowest key level', () => {
        expect(c.map.enemy.calculateHealthForKey(12345, 1, [])).toBe(12345);
    });
});

describe('polylineDefaultColor', () => {
    afterEach(() => {
        delete global.Cookies;
        delete global.randomColor;
    });

    it('returns the stored cookie color when one is set', () => {
        global.Cookies = {get: () => '#abcdef'};
        expect(polylineDefaultColor()).toBe('#abcdef');
    });

    it('falls back to a random color when the cookie is the string "null"', () => {
        global.Cookies = {get: () => 'null'};
        global.randomColor = () => '#123456';
        expect(polylineDefaultColor()).toBe('#123456');
    });
});
