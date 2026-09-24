// What EnemyVisual hands its canvas path. Methods are called off the prototype with a fake `this`,
// like enemyvisual.test.js does, since the constructor pulls in the whole EnemyVisualMain* hierarchy.

global.Signalable = class Signalable {
};
global.KillZone = class KillZone {
    constructor(color) {
        this.color = color;
    }
};
global.DeleteMapState = class DeleteMapState {
};
global.EditMapState = class EditMapState {
};
global.EnemySelection = class EnemySelection {
    drawsEnemyEditBorder() {
        return true;
    }
};
global.c = {map: {enemy: {calculateMargin: (size) => size / 10}}};
global.EnemyCanvasStyleProbe = require('./canvas/enemycanvasstyleprobe').EnemyCanvasStyleProbe;

const {EnemyVisual} = require('./enemyvisual');

const BASE_STYLE = {
    outerBackgroundColor: null,
    innerBackgroundColor: 'rgb(255, 255, 255)',
    innerImageUrl: 'https://assets.example/melee.png',
    innerImageFit: 'cover',
    innerImageBlendMode: 'source-over',
    contentBackgroundColor: null,
    contentImageFit: 'cover',
    contentImageBlendMode: 'source-over',
    innerBorderWidth: 0,
    innerBorderColor: null,
    innerBorderDashed: false,
    textColor: null,
    textFontStyle: '',
    textFontWeight: '',
    textFontFamily: '',
    textGlyph: null,
};

function makeFakeThis({style = {}, content = null, modifiers = [], mapState = null, killZone = null} = {}) {
    const reads = [];
    const boxReads = [];
    global.getState = () => ({
        getMapZoomLevel: () => 4,
        hasEnemyAggressivenessBorder: () => false,
        getUnkilledEnemyOpacity: () => 50,
        getUnkilledImportantEnemyOpacity: () => 80,
    });

    const fakeThis = {
        reads,
        boxReads,
        _canvasOpacity: 0.5,
        _canvasClasses: {outer: 'enemy_icon_npc_class', inner: 'enemy_icon melee patrol', innerStyle: 'border-color: blue;'},
        _modifiers: modifiers,
        mainVisual: {
            getSize: () => ({iconSize: [50, 50]}),
            getCanvasContent: () => content,
        },
        enemy: {
            isImportant: () => false,
            isSelectable: () => true,
            getKillZone: () => killZone,
            getOverpulledKillZoneId: () => null,
        },
        map: {
            getMapState: () => mapState,
            mapObjectGroupManager: {
                getEnemyMapObjectGroup: () => ({
                    getCanvasStyleProbe: () => ({
                        read: (...args) => {
                            reads.push(args);

                            return {...BASE_STYLE, ...style};
                        },
                        readBox: (classes) => {
                            boxReads.push(classes);

                            return {width: 16, height: 16, borderWidth: 4, classes};
                        },
                    }),
                }),
            },
        },
        _getSelectionState: EnemyVisual.prototype._getSelectionState,
    };

    return fakeThis;
}

function refresh(fakeThis) {
    let appearance = null;
    EnemyVisual.prototype._refreshCanvasPath.call(fakeThis, {setAppearance: (value) => appearance = value});

    return appearance;
}

test('_refreshCanvasPath_givenStateBorderInStyle_bakesItIntoSprite', () => {
    // Arrange
    const fakeThis = makeFakeThis({style: {innerBorderWidth: 3, innerBorderColor: 'rgb(0, 0, 255)', innerBorderDashed: true}});

    // Act
    const appearance = refresh(fakeThis);

    // Assert
    expect(fakeThis.reads[0]).toEqual(['enemy_icon_npc_class', 'enemy_icon melee patrol', '', 'border-color: blue;', null]);
    expect(appearance.sprite.stateBorder).toEqual({width: 3, color: 'rgb(0, 0, 255)', dashed: true});
    expect(appearance.sprite.text).toBeNull();
});

test('_refreshCanvasPath_givenNoStateBorder_leavesSpriteWithout', () => {
    // Arrange
    const fakeThis = makeFakeThis();

    // Act
    const appearance = refresh(fakeThis);

    // Assert
    expect(appearance.sprite.stateBorder).toBeNull();
    expect(appearance.badges).toEqual([]);
    expect(appearance.selection).toBeNull();
});

test('_refreshCanvasPath_givenTextContent_bakesTextAtItsSizeIntoSprite', () => {
    // Arrange
    const fakeThis = makeFakeThis({
        content: {classes: 'enemy_icon_npc_enemy_forces_inner', imageUrl: null, text: {value: '7', classes: 'my-auto w-100', fontSize: 14}},
        style: {textColor: 'rgb(255, 255, 255)', textFontWeight: '400', textFontFamily: 'Arial'},
    });

    // Act
    const appearance = refresh(fakeThis);

    // Assert
    expect(fakeThis.reads[0][4]).toBe('my-auto w-100');
    expect(appearance.sprite.text).toEqual({value: '7', font: '400 14px Arial', color: 'rgb(255, 255, 255)'});
});

test('_refreshCanvasPath_givenGlyphContent_drawsGlyphFromStyle', () => {
    // Arrange
    const fakeThis = makeFakeThis({
        content: {classes: 'portrait_inner', imageUrl: null, text: {value: null, classes: 'fa fa-times-circle', fontSize: 12}},
        style: {textColor: 'rgb(220, 53, 69)', textGlyph: 'X'},
    });

    // Act
    const appearance = refresh(fakeThis);

    // Assert
    expect(appearance.sprite.text.value).toBe('X');
    expect(appearance.sprite.imageUrl).toBeNull();
});

test('_refreshCanvasPath_givenModifiers_placesBadgesByOuterSize', () => {
    // Arrange
    const calls = [];
    const modifier = {
        getCanvasBadge: (...args) => {
            calls.push(args);

            return {classes: 'modifier truesight', left: -8, top: 0};
        },
    };
    const hidden = {getCanvasBadge: () => null};
    const fakeThis = makeFakeThis({modifiers: [modifier, hidden]});

    // Act
    const appearance = refresh(fakeThis);

    // Assert: 50px icon plus a 5px margin on either side
    expect(calls[0]).toEqual([4, 60, 60, 5]);
    expect(appearance.outerDiameter).toBe(60);
    expect(appearance.badges).toEqual([{box: {width: 16, height: 16, borderWidth: 4, classes: 'modifier truesight'}, left: -8, top: 0}]);
});

test('_refreshCanvasPath_givenSelectingMapState_addsHaloFourPixelsAroundEnemy', () => {
    // Arrange
    const fakeThis = makeFakeThis({mapState: new EnemySelection()});

    // Act
    const appearance = refresh(fakeThis);

    // Assert
    expect(fakeThis.boxReads).toEqual(['selected_enemy_icon leaflet-edit-marker-selected']);
    expect(appearance.selection.width).toBe(68);
    expect(appearance.selection.height).toBe(68);
});

test('_refreshCanvasPath_givenKillZone_drawsBorderInItsColourAtZoomWidth', () => {
    // Arrange
    const fakeThis = makeFakeThis({killZone: new KillZone('#ff0000')});

    // Act
    const appearance = refresh(fakeThis);

    // Assert
    expect(appearance.borderColor).toBe('#ff0000');
    expect(appearance.borderWidth).toBe(4);
});

test.each([
    ['notFaded', false, false, 1],
    ['faded', true, false, 0.5],
    ['fadedImportant', true, true, 0.8],
])('_updateBorder_givenCanvasPathAnd%s_setsOpacityAndRefreshesPath', (label, isFaded, isImportant, expected) => {
    // Arrange
    const fakeThis = makeFakeThis();
    fakeThis.enemy.isImportant = () => isImportant;
    const canvasPath = {};
    fakeThis.getCanvasPath = () => canvasPath;
    const refreshed = [];
    fakeThis._refreshCanvasPath = (path) => refreshed.push([path, fakeThis._canvasOpacity]);

    // Act
    EnemyVisual.prototype._updateBorder.call(fakeThis, '#00ff00', isFaded);

    // Assert
    expect(refreshed).toEqual([[canvasPath, expected]]);
});
