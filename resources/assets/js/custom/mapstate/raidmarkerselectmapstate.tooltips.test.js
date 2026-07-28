// The raid marker circle menu is drawn on top of the enemy it belongs to, so any enemy tooltip shown
// while it is open covers the menu items the user is aiming at (#3703). Suppression is a property of
// the map state (DungeonMap#setMapState() toggles the .map_enemy_tooltips_suppressed class off it),
// so it is pinned here.
//
// Follows the load-time-stub recipe from enemyselection/enemyselection.tooltips.test.js: stub what the
// class bodies touch when they are loaded, then require the sources.

// Only `Signalable` is needed at load time (for `extends`); everything else lives inside constructors
// and methods these tests never run.
global.Signalable = class Signalable {
};

// The real inheritance chain, so an inherited default would be the real one.
const {MapState} = require('./mapstate');
global.MapState = MapState;
const {MapObjectMapState} = require('./mapobjectmapstate');
global.MapObjectMapState = MapObjectMapState;

const {RaidMarkerSelectMapState} = require('./raidmarkerselectmapstate');

/**
 * Calls `disablesTooltips()` off the prototype, so no constructor (and none of its Leaflet/DOM wiring)
 * has to run.
 */
function disablesTooltips(cls) {
    return cls.prototype.disablesTooltips.call({});
}

describe('RaidMarkerSelectMapState tooltip suppression (#3703)', () => {
    it('disablesTooltips_givenRaidMarkerSelectMapState_returnsTrue', () => {
        // Arrange / Act
        const result = disablesTooltips(RaidMarkerSelectMapState);

        // Assert
        expect(result).toBe(true);
        expect(Object.prototype.hasOwnProperty.call(RaidMarkerSelectMapState.prototype, 'disablesTooltips')).toBe(true);
    });

    it.each([
        ['MapState', MapState],
        ['MapObjectMapState', MapObjectMapState],
    ])('disablesTooltips_givenABaseState_returnsFalse (%s)', (name, cls) => {
        // Arrange / Act
        const result = disablesTooltips(cls);

        // Assert
        // Pushing the suppression up the chain would silently take the tooltips away from every other
        // state too - pull-building in the route editor needs them (see enemyselection.tooltips.test.js).
        expect(result).toBe(false);
    });

    it('disablesTooltips_givenTheSharedMapObjectMapStateBase_isNotOverridden', () => {
        // Arrange / Act
        const ownProperty = Object.prototype.hasOwnProperty.call(MapObjectMapState.prototype, 'disablesTooltips');

        // Assert
        // Every EnemySelection extends this base too, so an override here would reach pull-building.
        expect(ownProperty).toBe(false);
    });
});
