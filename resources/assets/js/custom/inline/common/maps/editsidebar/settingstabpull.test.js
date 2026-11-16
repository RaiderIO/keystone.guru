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

/**
 * @param {Object|undefined} responseJson What the pull gradient endpoint answers.
 * @returns {{mapContext: Object, ajaxOptions: Object[]}}
 */
function stubSaveEnvironment(responseJson) {
    let pullGradient = '0% #ff0000,';
    const mapContext = {
        getPublicKey: () => 'abc1234',
        getPullGradient: () => pullGradient,
        getPullGradientApplyAlways: () => true,
        setPullGradient: vi.fn((value) => {
            pullGradient = value;
        }),
    };
    const ajaxOptions = [];
    global.getState = () => ({getMapContext: () => mapContext});
    global.$ = Object.assign(() => ({show: () => null, hide: () => null}), {
        ajax: (options) => {
            ajaxOptions.push(options);
            options.success(responseJson);
            options.complete();
        },
    });

    return {mapContext, ajaxOptions};
}

describe('SettingsTabPull._savePullGradientSettings', () => {
    test('_savePullGradientSettings_givenTheServerReturnsTheStoredGradient_resendsThatGradientNextTime', () => {
        // Arrange
        const {mapContext, ajaxOptions} = stubSaveEnvironment({pull_gradient: '0% #ff0000'});
        const settingsTabPull = new SettingsTabPull({});

        // Act
        settingsTabPull._savePullGradientSettings();
        settingsTabPull._savePullGradientSettings();

        // Assert
        expect(ajaxOptions[0].data.pull_gradient).toBe('0% #ff0000,');
        expect(mapContext.setPullGradient).toHaveBeenCalledWith('0% #ff0000');
        expect(ajaxOptions[1].data.pull_gradient).toBe('0% #ff0000');
    });

    test('_savePullGradientSettings_givenAResponseWithoutAGradient_keepsTheCurrentGradient', () => {
        // Arrange
        const {mapContext, ajaxOptions} = stubSaveEnvironment(undefined);
        const settingsTabPull = new SettingsTabPull({});

        // Act
        settingsTabPull._savePullGradientSettings();
        settingsTabPull._savePullGradientSettings();

        // Assert
        expect(mapContext.setPullGradient).not.toHaveBeenCalled();
        expect(ajaxOptions[1].data.pull_gradient).toBe('0% #ff0000,');
    });
});
