const constants = require('../constants');

global.MapContextDungeonExplore = class MapContextDungeonExplore {
    constructor(options) {
        this._options = options;
    }

    getMapIcons() {
        return [];
    }
};

const mapObjectGroups = {};
global.MAP_OBJECT_GROUP_NAMES_DUNGEON_ROUTE = constants.MAP_OBJECT_GROUP_NAMES_DUNGEON_ROUTE;
global.getMapObjectGroup                    = (name) => mapObjectGroups[name];

const redrawMapContents = vi.fn();
global.getState         = () => ({getDungeonMap: () => ({redrawMapContents})});

const {MapContextDungeonRouteSearch} = require('./mapcontextdungeonroutesearch');

const GETTER_TO_GROUP = {
    getPaths:         constants.MAP_OBJECT_GROUP_PATH,
    getBrushlines:    constants.MAP_OBJECT_GROUP_BRUSHLINE,
    getArrows:        constants.MAP_OBJECT_GROUP_ARROW,
    getKillZones:     constants.MAP_OBJECT_GROUP_KILLZONE,
    getMapIcons:      constants.MAP_OBJECT_GROUP_MAPICON,
    getKillZonePaths: constants.MAP_OBJECT_GROUP_KILLZONE_PATH,
};

function fakeGroup() {
    const group = {
        reset: vi.fn(() => group),
        load:  vi.fn(() => group),
    };

    return group;
}

describe('MapContextDungeonRouteSearch', () => {
    beforeEach(() => {
        for (const name of constants.MAP_OBJECT_GROUP_NAMES) {
            mapObjectGroups[name] = fakeGroup();
        }
        redrawMapContents.mockClear();
    });

    test('setDungeonRoute_givenRouteWithArrows_reloadsTheArrowGroup', () => {
        // Arrange
        const mapContext = new MapContextDungeonRouteSearch({});

        // Act
        mapContext.setDungeonRoute({publicKey: 'abc', arrows: [{id: 1}]});

        // Assert
        const arrowGroup = mapObjectGroups[constants.MAP_OBJECT_GROUP_ARROW];
        expect(arrowGroup.reset).toHaveBeenCalledTimes(1);
        expect(arrowGroup.load).toHaveBeenCalledTimes(1);
        expect(mapContext.getArrows()).toEqual([{id: 1}]);
        expect(redrawMapContents).toHaveBeenCalledTimes(1);
    });

    test('setDungeonRoute_givenNull_reloadsTheArrowGroupEmpty', () => {
        // Arrange
        const mapContext = new MapContextDungeonRouteSearch({dungeonRoute: {publicKey: 'abc', arrows: [{id: 1}]}});

        // Act
        mapContext.setDungeonRoute(null);

        // Assert
        expect(mapObjectGroups[constants.MAP_OBJECT_GROUP_ARROW].load).toHaveBeenCalledTimes(1);
        expect(mapContext.getArrows()).toEqual([]);
    });

    test('setDungeonRoute_givenSameRoute_doesNotReloadAnything', () => {
        // Arrange
        const mapContext = new MapContextDungeonRouteSearch({dungeonRoute: {publicKey: 'abc'}});

        // Act
        mapContext.setDungeonRoute({publicKey: 'abc'});

        // Assert
        expect(mapObjectGroups[constants.MAP_OBJECT_GROUP_ARROW].reset).not.toHaveBeenCalled();
        expect(redrawMapContents).not.toHaveBeenCalled();
    });

    test('routeLevelGroups_givenEveryGetterReadingTheSelectedRoute_areAllInTheResetList', () => {
        // Arrange
        const routeReadingGetters = Object.getOwnPropertyNames(MapContextDungeonRouteSearch.prototype)
            .filter((name) => name.startsWith('get') && MapContextDungeonRouteSearch.prototype[name].toString().includes('_options.dungeonRoute?.'));

        // Act
        const missingGroups = routeReadingGetters
            .filter((name) => !constants.MAP_OBJECT_GROUP_NAMES_DUNGEON_ROUTE.includes(GETTER_TO_GROUP[name]));

        // Assert
        expect(routeReadingGetters.sort()).toEqual(Object.keys(GETTER_TO_GROUP).sort());
        expect(missingGroups).toEqual([]);
    });
});
