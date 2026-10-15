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

describe('EnemyVisualModifier.updateVisibility', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    /**
     * Renders the attribute element the DOM template gives a teeming modifier, and a modifier whose
     * enemy records the handler it registers for `hidden`.
     */
    function makeRenderedModifier() {
        document.body.innerHTML = '<div id="map_enemy_visual_attribute_teeming_1"></div>';
        const handlers = {};
        const enemyVisual = makeEnemyVisual('visible');
        enemyVisual.enemy.register = (events, context, callback) => {
            for (const event of [].concat(events)) {
                handlers[event] = callback;
            }
        };
        const modifier = new EnemyVisualModifierTeeming(enemyVisual, 4);

        return {modifier, handlers, attribute: document.getElementById('map_enemy_visual_attribute_teeming_1')};
    }

    test('updateVisibility_givenZoomAtModifierZoom_showsAndPlacesTheAttribute', () => {
        // Arrange
        const {modifier, attribute} = makeRenderedModifier();
        attribute.style.display = 'none';

        // Act
        modifier.updateVisibility(3, 66, 66, 8);

        // Assert
        expect(attribute.style.display).toBe('');
        expect(attribute.style.left).toBe('-8px');
        expect(attribute.style.top).toBe('58px');
    });

    test('updateVisibility_givenZoomBelowModifierZoom_hidesTheAttribute', () => {
        // Arrange
        const {modifier, attribute} = makeRenderedModifier();

        // Act
        modifier.updateVisibility(2, 66, 66, 8);

        // Assert
        expect(attribute.style.display).toBe('none');
        expect(attribute.style.left).toBe('');
    });

    test('updateVisibility_givenStillHidden_doesNotLookTheAttributeUpAgain', () => {
        // Arrange
        const {modifier} = makeRenderedModifier();
        modifier.updateVisibility(2, 66, 66, 8);
        const getElementById = vi.spyOn(document, 'getElementById');

        // Act
        modifier.updateVisibility(2.5, 66, 66, 8);

        // Assert
        expect(getElementById).not.toHaveBeenCalled();
    });

    test('updateVisibility_givenEnemyHiddenInBetween_hidesTheRebuiltAttributeAgain', () => {
        // Arrange: hidden once, then the floor is switched away and back, which rebuilds the
        // attribute without the display the modifier set on it before
        const {modifier, handlers, attribute} = makeRenderedModifier();
        modifier.updateVisibility(2, 66, 66, 8);
        handlers.hidden({});
        attribute.style.display = '';

        // Act
        modifier.updateVisibility(2, 66, 66, 8);

        // Assert
        expect(attribute.style.display).toBe('none');
    });
});
