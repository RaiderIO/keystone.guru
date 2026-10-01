// ---------------------------------------------------------------------------
// Follows the global-script model recipe documented at the top of killzone.test.js: define the globals
// dungeonstart.js touches at LOAD time before require()-ing it, then hand the class fake collaborators
// instead of building a real DungeonMap.
// ---------------------------------------------------------------------------

global.L = {
    divIcon: function (options) {
        return {options};
    },
    Marker: {extend: (o) => o},
    Draw: {
        Marker: {extend: () => function () {}},
        Feature: {prototype: {initialize() {}}},
    },
};

global.MapContextMappingVersionEdit = class MapContextMappingVersionEdit {
};
global.EditMapState = class EditMapState {
};
global.DeleteMapState = class DeleteMapState {
};

global.Attribute = class Attribute {
    constructor(options) {
        this.options = options;
        this.name = options.name;
    }
};

global.c = {
    map: {
        mapicon: {calculateSize: (value) => value},
        sanitizeText: (text) => text,
    },
};

global.Handlebars = {
    templates: {
        map_map_icon_visual_template: (data) => JSON.stringify(data),
    },
};

global.lang = {
    get: (key) => `translated(${key})`,
};

let mapContext = null;
global.getState = () => ({
    getMapContext: () => mapContext,
    getCurrentFloor: () => ({id: 7}),
    register: () => {},
    unregister: () => {},
});

// Lightweight immediate base class standing in for VersionableMapObject, providing only what DungeonStart
// calls on `super`/`this`.
global.VersionableMapObject = class VersionableMapObject {
    constructor(map, layer = null, options = {}) {
        this.map = map;
        this.layer = layer;
        this.options = options;
        this._cachedAttributes = null;
        this.comment = null;
    }

    _getAttributes() {
        return [new Attribute({name: 'mapping_version_id', edit: false})];
    }

    register() {}

    unregister() {}

    unbindTooltip() {}

    onLayerInit() {}

    cleanup() {}
};

const {DungeonStart} = require('./dungeonstart.js');

/**
 * @returns {{icon: Object|null, tooltip: String|null, setIcon: Function, bindTooltip: Function, getLatLng: Function}}
 */
function fakeLayer() {
    return {
        icon: null,
        tooltip: null,
        setIcon(icon) {
            this.icon = icon;
        },
        bindTooltip(text) {
            this.tooltip = text;
        },
        getLatLng: () => ({lat: 1, lng: 2}),
    };
}

/**
 * @param {Object} context
 * @param {Object|null} mapState
 * @returns {DungeonStart}
 */
function buildDungeonStart(context, mapState = null) {
    mapContext = context;

    return new DungeonStart({
        options: {assetsBaseUrl: 'https://assets'},
        register: () => {},
        unregister: () => {},
        getMapState: () => mapState,
    }, fakeLayer());
}

describe('DungeonStart', () => {
    it('constructor_givenAnyContext_isNotAMapIcon', () => {
        // Arrange & Act
        const dungeonStart = buildDungeonStart(new MapContextMappingVersionEdit());

        // Assert
        expect(dungeonStart.options.name).toBe('dungeonstart');
        expect(dungeonStart.map_icon_type_id).toBeUndefined();
        expect(dungeonStart.map_icon_type).toBeUndefined();
    });

    it('_getAttributes_givenAnyContext_returnsTheDungeonStartFields', () => {
        // Arrange
        const dungeonStart = buildDungeonStart(new MapContextMappingVersionEdit());

        // Act
        const attributes = dungeonStart._getAttributes(true);

        // Assert
        expect(attributes.map((attribute) => attribute.name)).toEqual(
            ['mapping_version_id', 'floor_id', 'target_dungeon_id', 'comment', 'lat', 'lng'],
        );
        const targetDungeonAttribute = attributes.find((attribute) => attribute.name === 'target_dungeon_id');
        expect(targetDungeonAttribute.options.edit).toBe(false);
        expect(targetDungeonAttribute.options.save).toBe(false);
        expect(attributes.find((attribute) => attribute.name === 'comment').options.edit).toBeUndefined();
        expect(attributes.find((attribute) => attribute.name === 'floor_id').options.default).toBe(7);
    });

    it('onLayerInit_givenNoMapState_rendersTheDungeonStartImage', () => {
        // Arrange
        const dungeonStart = buildDungeonStart(new MapContextMappingVersionEdit());

        // Act
        dungeonStart.onLayerInit();

        // Assert
        const html = JSON.parse(dungeonStart.layer.icon.options.html);
        expect(html.icon_url).toBe('https://assets/images/mapicon/dungeon_start.png');
        expect(html.selectedclass).toBe('');
        expect(html.outer_width).toBe(24);
        expect(dungeonStart.layer.icon.options.className).toBe('map_icon map_icon_dungeon_start');
    });

    it('onLayerInit_givenEditMapStateInMappingVersionEdit_rendersTheSelectedImage', () => {
        // Arrange
        const dungeonStart = buildDungeonStart(new MapContextMappingVersionEdit(), new EditMapState());

        // Act
        dungeonStart.onLayerInit();

        // Assert
        const html = JSON.parse(dungeonStart.layer.icon.options.html);
        expect(html.selectedclass).toBe(' leaflet-edit-marker-selected');
        expect(html.outer_width).toBe(32);
    });

    it('bindTooltip_givenNoComment_usesTheDefaultTooltip', () => {
        // Arrange
        const dungeonStart = buildDungeonStart(new MapContextMappingVersionEdit());

        // Act
        dungeonStart.bindTooltip();

        // Assert
        expect(dungeonStart.layer.tooltip).toBe('translated(js.dungeonstart_tooltip)');
    });

    it('bindTooltip_givenComment_translatesTheComment', () => {
        // Arrange
        const dungeonStart = buildDungeonStart(new MapContextMappingVersionEdit());
        dungeonStart.comment = 'mapping.map_icons.sl.plaguefall.exit';

        // Act
        dungeonStart.bindTooltip();

        // Assert
        expect(dungeonStart.layer.tooltip).toBe('translated(mapping.map_icons.sl.plaguefall.exit)');
    });

    it('isEditable_givenMappingVersionEditContext_returnsTrue', () => {
        // Arrange
        const dungeonStart = buildDungeonStart(new MapContextMappingVersionEdit());

        // Act & Assert
        expect(dungeonStart.isEditable()).toBe(true);
        expect(dungeonStart.isDeletable()).toBe(true);
    });

    it('isEditable_givenAnyOtherContext_returnsFalse', () => {
        // Arrange
        const dungeonStart = buildDungeonStart({});

        // Act & Assert
        expect(dungeonStart.isEditable()).toBe(false);
        expect(dungeonStart.isDeletable()).toBe(false);
    });
});
