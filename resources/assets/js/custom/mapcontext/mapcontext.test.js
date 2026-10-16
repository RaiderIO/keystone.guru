// MapContext extends the bundle-global Signalable; a bare stub is enough to load the class.
global.Signalable = class Signalable {
};

const {MapContext} = require('./mapcontext');

describe('MapContext', () => {
    test('getDungeonTransports_givenDungeonDataCachedWithoutTransports_returnsAnEmptyList', () => {
        // Arrange - dungeon data cached before transports existed has no dungeonTransports key
        const mapContext = Object.create(MapContext.prototype);
        mapContext._options = {dungeon: {dungeonStarts: []}};

        // Act
        const dungeonTransports = mapContext.getDungeonTransports();

        // Assert
        expect(dungeonTransports).toEqual([]);
    });

    test('getDungeonTransports_givenDungeonDataWithTransports_returnsThem', () => {
        // Arrange
        const mapContext = Object.create(MapContext.prototype);
        mapContext._options = {dungeon: {dungeonTransports: [{id: 7}]}};

        // Act
        const dungeonTransports = mapContext.getDungeonTransports();

        // Assert
        expect(dungeonTransports).toEqual([{id: 7}]);
    });
});
