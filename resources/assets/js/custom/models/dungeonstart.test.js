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
    getDungeonSelectValues(includeCurrentDungeon) {
        return includeCurrentDungeon === false
            ? [{id: 80, name: 'The Deadmines'}]
            : [{id: 80, name: 'The Deadmines'}, {id: 1, name: 'The Current Dungeon'}];
    }

    getDungeonStartNavigation() {
        return null;
    }
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
    get: (key, replacements = null) => replacements === null ?
        `translated(${key})` :
        `translated(${key}, ${JSON.stringify(replacements)})`,
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
        handlers: {},
        on(event, handler) {
            this.handlers[event] = handler;

            return this;
        },
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
 * An explore page's map context, where the given dungeon starts navigate.
 *
 * @param {Object} navigationById
 * @returns {Object}
 */
function exploreContext(navigationById = {}) {
    return {
        getDungeonStartNavigation: (dungeonStartId) => navigationById[dungeonStartId] ?? null,
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
            [
                'mapping_version_id', 'floor_id', 'target_dungeon_id', 'comment', 'raid',
                'min_suggested_level', 'max_suggested_level', 'lat', 'lng',
            ],
        );
        for (const name of ['min_suggested_level', 'max_suggested_level']) {
            const levelAttribute = attributes.find((attribute) => attribute.name === name);
            expect(levelAttribute.options.edit).toBe(false);
            expect(levelAttribute.options.save).toBe(false);
            expect(levelAttribute.options.default).toBeNull();
        }
        const raidAttribute = attributes.find((attribute) => attribute.name === 'raid');
        expect(raidAttribute.options.edit).toBe(false);
        expect(raidAttribute.options.save).toBe(false);
        expect(raidAttribute.options.default).toBe(false);
        const targetDungeonAttribute = attributes.find((attribute) => attribute.name === 'target_dungeon_id');
        expect(targetDungeonAttribute.options.type).toBe('select');
        expect(targetDungeonAttribute.options.edit).toBeUndefined();
        expect(targetDungeonAttribute.options.save).toBeUndefined();
        expect(targetDungeonAttribute.options.values()).toEqual([{id: 80, name: 'The Deadmines'}]);
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

    it('onLayerInit_givenRaidStart_rendersTheRaidStartImage', () => {
        // Arrange
        const dungeonStart = buildDungeonStart(new MapContextMappingVersionEdit());
        dungeonStart.raid = true;

        // Act
        dungeonStart.onLayerInit();

        // Assert
        const html = JSON.parse(dungeonStart.layer.icon.options.html);
        expect(html.key).toBe('raid_start');
        expect(html.icon_url).toBe('https://assets/images/mapicon/raid_start.png');
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

    it('bindTooltip_givenNavigationToADungeon_saysGoToThatDungeon', () => {
        // Arrange
        const dungeonStart = buildDungeonStart(exploreContext({
            5: {backLink: false, dungeonName: 'dungeons.classic.deadmines', url: 'https://keystone.guru/start/5'},
        }));
        dungeonStart.id = 5;
        dungeonStart.comment = 'mapping.map_icons.sl.plaguefall.exit';

        // Act
        dungeonStart.bindTooltip();

        // Assert
        expect(dungeonStart.layer.tooltip).toBe(
            'translated(js.dungeonstart_go_to_label, {"dungeon":"translated(dungeons.classic.deadmines)"})',
        );
    });

    it('bindTooltip_givenBackLink_saysBackToThatDungeon', () => {
        // Arrange
        const dungeonStart = buildDungeonStart(exploreContext({
            5: {backLink: true, dungeonName: 'dungeons.continent.eastern_kingdoms', url: 'https://keystone.guru/start/5'},
        }));
        dungeonStart.id = 5;

        // Act
        dungeonStart.bindTooltip();

        // Assert
        expect(dungeonStart.layer.tooltip).toBe(
            'translated(js.dungeonstart_back_to_label, {"dungeon":"translated(dungeons.continent.eastern_kingdoms)"})',
        );
    });

    it('bindTooltip_givenNavigationOfAnotherStart_usesTheDefaultTooltip', () => {
        // Arrange
        const dungeonStart = buildDungeonStart(exploreContext({
            6: {backLink: false, dungeonName: 'dungeons.classic.deadmines', url: 'https://keystone.guru/start/6'},
        }));
        dungeonStart.id = 5;

        // Act
        dungeonStart.bindTooltip();

        // Assert
        expect(dungeonStart.layer.tooltip).toBe('translated(js.dungeonstart_tooltip)');
    });

    it('bindTooltip_givenNavigationToADungeonWithSuggestedLevels_addsTheLevelRangeOnANewLine', () => {
        // Arrange
        const dungeonStart = buildDungeonStart(exploreContext({
            5: {backLink: false, dungeonName: 'dungeons.classic.the_hall_of_thanes', url: 'https://keystone.guru/start/5'},
        }));
        dungeonStart.id = 5;
        dungeonStart.min_suggested_level = 13;
        dungeonStart.max_suggested_level = 18;

        // Act
        dungeonStart.bindTooltip();

        // Assert
        expect(dungeonStart.layer.tooltip).toBe(
            'translated(js.dungeonstart_go_to_label, {"dungeon":"translated(dungeons.classic.the_hall_of_thanes)"})\n' +
            'translated(js.dungeonstart_suggested_level_range, {"min":13,"max":18})',
        );
    });

    it('bindTooltip_givenNoNavigationAndSuggestedLevels_addsTheLevelRangeToTheDefaultTooltip', () => {
        // Arrange
        const dungeonStart = buildDungeonStart(new MapContextMappingVersionEdit());
        dungeonStart.min_suggested_level = 13;
        dungeonStart.max_suggested_level = 18;

        // Act
        dungeonStart.bindTooltip();

        // Assert
        expect(dungeonStart.layer.tooltip).toBe(
            'translated(js.dungeonstart_tooltip)\ntranslated(js.dungeonstart_suggested_level_range, {"min":13,"max":18})',
        );
    });

    it('bindTooltip_givenBackLinkWithSuggestedLevels_leavesTheLevelRangeOut', () => {
        // Arrange
        const dungeonStart = buildDungeonStart(exploreContext({
            5: {backLink: true, dungeonName: 'dungeons.classic.eastern_kingdoms', url: 'https://keystone.guru/start/5'},
        }));
        dungeonStart.id = 5;
        dungeonStart.min_suggested_level = 13;
        dungeonStart.max_suggested_level = 18;

        // Act
        dungeonStart.bindTooltip();

        // Assert
        expect(dungeonStart.layer.tooltip).toBe(
            'translated(js.dungeonstart_back_to_label, {"dungeon":"translated(dungeons.classic.eastern_kingdoms)"})',
        );
    });

    it.each([
        [13, 18, 'translated(js.dungeonstart_suggested_level_range, {"min":13,"max":18})'],
        [60, 60, 'translated(js.dungeonstart_suggested_level, {"level":60})'],
        [13, null, 'translated(js.dungeonstart_suggested_level_min, {"min":13})'],
        [null, 18, 'translated(js.dungeonstart_suggested_level_max, {"max":18})'],
        [null, null, null],
    ])('getSuggestedLevelText_givenMin%sAndMax%s_returnsTheMatchingText', (min, max, expected) => {
        // Arrange
        const dungeonStart = buildDungeonStart(new MapContextMappingVersionEdit());
        dungeonStart.min_suggested_level = min;
        dungeonStart.max_suggested_level = max;

        // Act
        const text = dungeonStart.getSuggestedLevelText();

        // Assert
        expect(text).toBe(expected);
    });

    describe('click', () => {
        let assignedHref;

        beforeEach(() => {
            // jsdom refuses a real navigation, so stand in for it
            assignedHref = null;

            delete window.location;
            window.location = {
                set href(url) {
                    assignedHref = url;
                },
                get href() {
                    return assignedHref;
                },
            };
        });

        it('onLayerInit_givenNavigationThenClick_navigatesToItsUrl', () => {
            // Arrange
            const dungeonStart = buildDungeonStart(exploreContext({
                5: {backLink: false, dungeonName: 'dungeons.classic.deadmines', url: 'https://keystone.guru/start/5'},
            }));
            dungeonStart.id = 5;
            dungeonStart.onLayerInit();

            // Act
            dungeonStart.layer.handlers.click?.();

            // Assert
            expect(assignedHref).toBe('https://keystone.guru/start/5');
        });

        it('onLayerInit_givenNoNavigationThenClick_staysOnThePage', () => {
            // Arrange
            const dungeonStart = buildDungeonStart(exploreContext({
                6: {backLink: false, dungeonName: 'dungeons.classic.deadmines', url: 'https://keystone.guru/start/6'},
            }));
            dungeonStart.id = 5;
            dungeonStart.onLayerInit();

            // Act
            dungeonStart.layer.handlers.click();

            // Assert
            expect(assignedHref).toBeNull();
        });
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
