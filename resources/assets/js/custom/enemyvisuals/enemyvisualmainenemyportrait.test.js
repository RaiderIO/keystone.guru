global.Signalable = class Signalable {
};
global.EnemyVisualIcon = class EnemyVisualIcon extends Signalable {
};
global.EnemyVisualMain = class EnemyVisualMain extends EnemyVisualIcon {
    _getTextWidth(textLength = 1) {
        return 10 + textLength;
    }
};

const {EnemyVisualMainEnemyPortrait} = require('./enemyvisualmainenemyportrait');

function makePortrait({obsolete = false, overpulledKillZoneId = null, npc = {enemy_portrait_url: 'images/enemyportraits/1.png'}} = {}) {
    const portrait = Object.create(EnemyVisualMainEnemyPortrait.prototype);
    portrait.enemyvisual = {
        map: {options: {assetsBaseUrl: 'https://assets.example'}},
        enemy: {
            npc: npc,
            isObsolete: () => obsolete,
            getOverpulledKillZoneId: () => overpulledKillZoneId,
        },
    };

    return portrait;
}

test('getCanvasContent_givenRegularEnemy_returnsPortraitWithoutText', () => {
    // Arrange
    const portrait = makePortrait();

    // Act
    const content = portrait.getCanvasContent();

    // Assert
    expect(content).toEqual({
        classes: 'enemy_icon_npc_enemy_portrait_inner',
        imageUrl: 'https://assets.example/images/enemyportraits/1.png',
        text: null,
    });
});

test('getCanvasContent_givenNoNpc_returnsUnknownPortrait', () => {
    // Arrange
    const portrait = makePortrait({npc: null});

    // Act
    const content = portrait.getCanvasContent();

    // Assert
    expect(content.imageUrl).toBe('https://assets.example/images/enemyportraits/unknown.png');
});

test('getCanvasContent_givenObsoleteEnemy_returnsTimesGlyphInsteadOfPortrait', () => {
    // Arrange
    const portrait = makePortrait({obsolete: true, overpulledKillZoneId: 5});

    // Act
    const content = portrait.getCanvasContent();

    // Assert
    expect(content.imageUrl).toBeNull();
    expect(content.text).toEqual({value: null, classes: 'obsolete text-danger fa fa-times-circle', fontSize: 13});
});

test('getCanvasContent_givenOverpulledEnemy_returnsPlusGlyphInsteadOfPortrait', () => {
    // Arrange
    const portrait = makePortrait({overpulledKillZoneId: 5});

    // Act
    const content = portrait.getCanvasContent();

    // Assert
    expect(content.imageUrl).toBeNull();
    expect(content.text).toEqual({value: null, classes: 'overpulled text-success fa fa-plus-circle', fontSize: 13});
});
