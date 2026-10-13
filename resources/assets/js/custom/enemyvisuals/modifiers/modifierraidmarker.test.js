global.EnemyVisualIcon = class EnemyVisualIcon {
    constructor(enemyvisual) {
        this.enemyvisual = enemyvisual;
        this.iconName = '';
    }
};
global.$.extend = (target, ...sources) => Object.assign(target, ...sources);

const {EnemyVisualModifier} = require('./modifier');
global.EnemyVisualModifier = EnemyVisualModifier;
const {EnemyVisualModifierRaidMarker} = require('./modifierraidmarker');

function makeEnemyVisual(raidMarkerKey) {
    return {
        register: () => {
        },
        enemy: {
            id: 1,
            raid_marker_key: raidMarkerKey,
            register: () => {
            },
        },
    };
}

describe('EnemyVisualModifierRaidMarker', () => {
    const originalGetState = global.getState;

    afterEach(() => {
        global.getState = originalGetState;
    });

    test('constructor_givenEnemyWithRaidMarkerKey_usesKeyAsIconName', () => {
        // Arrange
        const enemyVisual = makeEnemyVisual('skull');

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
        const modifier = new EnemyVisualModifierRaidMarker(makeEnemyVisual(''), 0);

        // Act
        const validIconNames = modifier._getValidIconNames();

        // Assert
        expect(validIconNames).toEqual(['', 'star', 'skull']);
    });

    test('getCanvasBadge_givenRaidMarker_returnsItsIconCentredOnTheEnemy', () => {
        // Arrange
        const modifier = new EnemyVisualModifierRaidMarker(makeEnemyVisual('skull'), 0);

        // Act
        const badge = modifier.getCanvasBadge(1, 66, 66, 8);

        // Assert
        expect(badge).toEqual({classes: 'modifier raid_marker_enemy_icon skull_enemy_icon', left: 20, top: 20});
    });

    test('getCanvasBadge_givenNoRaidMarker_returnsNull', () => {
        // Arrange
        const modifier = new EnemyVisualModifierRaidMarker(makeEnemyVisual(''), 0);

        // Act
        const badge = modifier.getCanvasBadge(1, 66, 66, 8);

        // Assert
        expect(badge).toBeNull();
    });
});
