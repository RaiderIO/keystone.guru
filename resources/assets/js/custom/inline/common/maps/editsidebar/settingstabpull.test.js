global.SettingsTab = class {
};

const {SettingsTabPull} = require('./settingstabpull');

describe('SettingsTabPull.formatHandlerPosition', () => {
    test.each([
        ['an exponent-notation float residue', 2.220446049250313e-16, '0'],
        ['a tiny position that String() renders as 1e-7', 1e-7, '0'],
        ['a small position', 0.25, '0.25'],
        ['a whole number', 50, '50'],
        ['a fraction', 47.05882352941177, '47.0588'],
        ['a trailing-zero fraction', 12.5, '12.5'],
        ['zero', 0, '0'],
        ['the right edge', 100, '100'],
        ['a position past the right edge', 100.5, '100'],
        ['a negative position', -3, '0'],
        ['a non-number', NaN, '0'],
    ])('formatHandlerPosition_given%s_returnsAPlainDecimal', (label, position, expected) => {
        // Arrange - nothing beyond the position

        // Act
        const result = SettingsTabPull.formatHandlerPosition(position);

        // Assert
        expect(result).toBe(expected);
        expect(result).toMatch(/^\d{1,3}(\.\d+)?$/);
    });
});
