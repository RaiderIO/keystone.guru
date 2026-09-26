global.EnemyVisualIcon = class EnemyVisualIcon {
    constructor(enemyvisual) {
        this.enemyvisual = enemyvisual;
        this.iconName = '';
    }
};
global.$.extend = (target, ...sources) => Object.assign(target, ...sources);
global.c = {map: {enemy: {teeming_display_zoom: 3}}};

const {EnemyVisualModifier} = require('./modifier');
global.EnemyVisualModifier = EnemyVisualModifier;
const {EnemyVisualModifierTeeming} = require('./modifierteeming');

function makeEnemyVisual(teeming) {
    return {
        register: () => {
        },
        enemy: {
            id: 1,
            teeming: teeming,
            register: () => {
            },
        },
    };
}

test('getCanvasBadge_givenVisibleTeemingAtItsZoom_returnsBadgeAtBottomLeft', () => {
    // Arrange
    const modifier = new EnemyVisualModifierTeeming(makeEnemyVisual('visible'), 4);

    // Act
    const badge = modifier.getCanvasBadge(3, 66, 66, 8);

    // Assert
    expect(badge).toEqual({classes: 'modifier modifier_external teeming', left: -8, top: 58});
});

test('getCanvasBadge_givenZoomBelowModifierZoom_returnsNull', () => {
    // Arrange
    const modifier = new EnemyVisualModifierTeeming(makeEnemyVisual('visible'), 4);

    // Act
    const badge = modifier.getCanvasBadge(2.9, 66, 66, 8);

    // Assert
    expect(badge).toBeNull();
});

test('getCanvasBadge_givenNoIcon_returnsNull', () => {
    // Arrange
    const modifier = new EnemyVisualModifierTeeming(makeEnemyVisual(null), 4);

    // Act
    const badge = modifier.getCanvasBadge(5, 66, 66, 8);

    // Assert
    expect(badge).toBeNull();
});
