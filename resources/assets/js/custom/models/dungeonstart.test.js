// ---------------------------------------------------------------------------
// Follows the global-script model recipe documented at the top of killzone.test.js: define the globals
// dungeonstart.js touches at LOAD time before require()-ing it, then hand the class fake collaborators
// instead of building a real DungeonMap.
// ---------------------------------------------------------------------------

global.L = {
    Draw: {
        Marker: {extend: () => function () {}},
        Feature: {prototype: {initialize() {}}},
    },
};
global.LeafletIconUnknown = {};
global.MAP_ICON_TYPE_DUNGEON_START_ID = 10;

global.MapContextMappingVersionEdit = class MapContextMappingVersionEdit {
};

let mapContext = null;
global.getState = () => ({
    getMapContext: () => mapContext,
});

// Lightweight immediate base class standing in for Icon, providing only what DungeonStart calls on
// `super`/`this`.
global.Icon = class Icon {
    constructor(map, layer = null, options = {}) {
        this.map = map;
        this.layer = layer;
        this.options = options;
        this._cachedAttributes = null;
        this.comment = null;
        this.map_icon_type = null;
    }

    _getAttributes() {
        return [
            {options: {name: 'floor_id', edit: false}},
            {options: {name: 'map_icon_type_id', edit: true, default: null}},
            {options: {name: 'comment', edit: true}},
        ];
    }

    setMapIconType(mapIconType) {
        this.map_icon_type = mapIconType;
        this.map_icon_type_id = mapIconType.id;
    }

    isEditable() {
        return true;
    }
};

const {DungeonStart} = require('./dungeonstart.js');

/**
 * @param {Object} context
 * @returns {DungeonStart}
 */
function buildDungeonStart(context) {
    mapContext = Object.assign(context, {
        getMapIconType: (id) => ({id: id, name: 'mapicontypes.dungeon_start'}),
    });

    return new DungeonStart({}, null);
}

describe('DungeonStart', () => {
    it('constructor_givenAnyContext_usesTheDungeonStartMapIconType', () => {
        // Arrange & Act
        const dungeonStart = buildDungeonStart(new MapContextMappingVersionEdit());

        // Assert
        expect(dungeonStart.map_icon_type_id).toBe(10);
        expect(dungeonStart.options.name).toBe('dungeonstart');
    });

    it('_getAttributes_givenInheritedMapIconTypeAttribute_neitherEditsNorSavesIt', () => {
        // Arrange
        const dungeonStart = buildDungeonStart(new MapContextMappingVersionEdit());

        // Act
        const attributes = dungeonStart._getAttributes(true);

        // Assert
        const mapIconTypeAttribute = attributes.find((attribute) => attribute.options.name === 'map_icon_type_id');
        expect(mapIconTypeAttribute.options.edit).toBe(false);
        expect(mapIconTypeAttribute.options.save).toBe(false);
        expect(mapIconTypeAttribute.options.default).toBe(10);

        const commentAttribute = attributes.find((attribute) => attribute.options.name === 'comment');
        expect(commentAttribute.options.edit).toBe(true);
    });

    it('isEditable_givenMappingVersionEditContext_returnsTrue', () => {
        // Arrange
        const dungeonStart = buildDungeonStart(new MapContextMappingVersionEdit());

        // Act & Assert
        expect(dungeonStart.isEditable()).toBe(true);
    });

    it('isEditable_givenAnyOtherContext_returnsFalse', () => {
        // Arrange
        const dungeonStart = buildDungeonStart({});

        // Act & Assert
        expect(dungeonStart.isEditable()).toBe(false);
    });
});
