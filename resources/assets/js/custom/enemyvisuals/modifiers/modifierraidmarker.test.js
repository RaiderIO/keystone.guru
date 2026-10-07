global.EnemyVisualModifier = class EnemyVisualModifier {
    constructor(enemyvisual, index) {
        this.enemyvisual = enemyvisual;
        this.index = index;
    }
};

const {EnemyVisualModifierRaidMarker} = require('./modifierraidmarker');

describe('EnemyVisualModifierRaidMarker', () => {
    const originalGetState = global.getState;

    afterEach(() => {
        global.getState = originalGetState;
    });

    test('constructor_givenEnemyWithRaidMarkerKey_usesKeyAsIconName', () => {
        // Arrange
        const enemyVisual = {enemy: {raid_marker_key: 'skull'}};

        // Act
        const modifier = new EnemyVisualModifierRaidMarker(enemyVisual, 0);

        // Assert
        expect(modifier.iconName).toBe('skull');
    });

    test('_getValidIconNames_givenStaticRaidMarkers_returnsEmptyPlusEveryKey', () => {
        // Arrange
        global.getState = () => ({
            getMapContext: () => ({
                getStaticRaidMarkers: () => [{id: 1, key: 'star'}, {id: 8, key: 'skull'}],
            }),
        });
        const modifier = new EnemyVisualModifierRaidMarker({enemy: {raid_marker_key: ''}}, 0);

        // Act
        const validIconNames = modifier._getValidIconNames();

        // Assert
        expect(validIconNames).toEqual(['', 'star', 'skull']);
    });
});
