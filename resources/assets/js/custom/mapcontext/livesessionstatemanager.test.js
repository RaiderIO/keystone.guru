// ---------------------------------------------------------------------------
// LiveSessionStateManager applies the live session state to the map, on page load and for every broadcast. These
// tests use the real LiveSessionState, MapContextLiveSession and LiveSessionEnemy (on a stub Enemy base) and fake the
// map, its object groups and the kill zones.
// ---------------------------------------------------------------------------

const {Signalable} = require('../signalable.js');
global.Signalable = Signalable;

global.MAP_OBJECT_GROUP_ENEMY = 'enemy';
global.MAP_OBJECT_GROUP_KILLZONE = 'killzone';
global.MAP_OBJECT_GROUP_PLAYER_POSITION = 'playerposition';
global.MAP_FACADE_STYLE_FACADE = 'facade';
global.MAP_FACADE_STYLE_SPLIT_FLOORS = 'split_floors';

global.Enemy = class Enemy extends Signalable {
    constructor(map, layer) {
        super();
        this.map = map;
        this.layer = layer;
    }
};
global.MapContextDungeonRoute = class MapContextDungeonRoute {
    constructor(options) {
        this._options = options;
    }
};
global.SelectKillZoneEnemySelectionOverpull = class SelectKillZoneEnemySelectionOverpull {
    constructor(hasChanges) {
        this._hasChanges = hasChanges;
    }

    hasChanges() {
        return this._hasChanges;
    }
};

const {LiveSessionState} = require('./livesessionstate.js');
global.LiveSessionState = LiveSessionState;
const {LiveSessionEnemy} = require('../models/livesessionenemy.js');
global.LiveSessionEnemy = LiveSessionEnemy;
const {MapContextLiveSession} = require('./mapcontextlivesession.js');
const {LiveSessionStateManager} = require('./livesessionstatemanager.js');

/**
 * Records the calls the manager makes on a pull, in order, and holds the enemies it was told about.
 */
global.LiveSessionKillZone = class LiveSessionKillZone {
    constructor(id, calls) {
        this.id = id;
        this.calls = calls;
        this.overpulledEnemies = [];
    }

    addOverpulledEnemy(enemy) {
        this.calls.push(`kz${this.id}.addOverpulledEnemy(${enemy.id})`);
        if (!this.overpulledEnemies.includes(enemy.id)) {
            this.overpulledEnemies.push(enemy.id);
        }
    }
};

global.PlayerPositionMapObjectGroup = class PlayerPositionMapObjectGroup {
    constructor() {
        this.createOrUpdatePlayerPosition = vi.fn();
    }
};

function makeEnemy(id, calls) {
    const enemy = new LiveSessionEnemy(null, null);
    enemy.id = id;
    const setOverpulledKillZoneId = enemy.setOverpulledKillZoneId.bind(enemy);
    enemy.setOverpulledKillZoneId = (killZoneId) => {
        calls.push(`enemy${id}.setOverpulledKillZoneId(${killZoneId})`);
        setOverpulledKillZoneId(killZoneId);
    };
    return enemy;
}

function arrangeMap({facadeEnabled = false, facadeStyle = 'split_floors', mapContextState = {}} = {}) {
    const calls = [];
    const enemies = {1: makeEnemy(1, calls), 2: makeEnemy(2, calls), 3: makeEnemy(3, calls)};
    const killZones = {7: new LiveSessionKillZone(7, calls), 8: new LiveSessionKillZone(8, calls)};
    const playerPositionMapObjectGroup = new PlayerPositionMapObjectGroup();
    const groups = {
        enemy: {objects: enemies},
        killzone: {findMapObjectById: (id) => killZones[id] ?? null},
        playerposition: playerPositionMapObjectGroup,
    };

    const map = new Signalable();
    map.mapState = null;
    map.getMapState = () => map.mapState;
    map.mapObjectGroupManager = {getByName: (name) => groups[name]};

    const mapContext = new MapContextLiveSession({liveSessionState: mapContextState});
    global.getState = () => ({
        getMapContext: () => mapContext,
        isCurrentDungeonFacadeEnabled: () => facadeEnabled,
        getMapFacadeStyle: () => facadeStyle,
    });

    const manager = new LiveSessionStateManager(map);

    return {map, manager, mapContext, enemies, killZones, playerPositionMapObjectGroup, calls};
}

const player = {
    guid: 'Player-1084-0A6BB627',
    name: 'Herochar',
    class_id: 7,
    specialization_id: 262,
    specialization_icon_url: 'https://example.test/262.png',
    split_floors: [-10.5, 20.25, 3],
    facade: [-50, 60, 9],
};

describe('LiveSessionStateManager', () => {
    describe('page load', () => {
        test('mapobjectgroupsloaded_givenTheMapContextsState_appliesItToTheEnemies', () => {
            // Arrange
            const {map, enemies} = arrangeMap({
                mapContextState: {killed: [1], in_combat: [2], obsolete: [3], overpulled: [], players: []},
            });

            // Act
            map.signal('map:mapobjectgroupsloaded');

            // Assert
            expect(enemies[1].isKilled()).toBe(true);
            expect(enemies[2].isInCombat()).toBe(true);
            expect(enemies[3].isObsolete()).toBe(true);
            expect(enemies[1].isInCombat()).toBe(false);
        });

        test('setState_givenTheMapHasNotLoadedYet_onlyStoresTheState', () => {
            // Arrange
            const {map, manager, mapContext, enemies} = arrangeMap();

            // Act
            manager.setState({killed: [2], in_combat: [], obsolete: [], overpulled: [], players: []});

            // Assert
            expect(enemies[2].isKilled()).toBe(false);
            expect([...mapContext.getLiveSessionState().killedEnemyIds]).toEqual([2]);

            map.signal('map:mapobjectgroupsloaded');
            expect(enemies[2].isKilled()).toBe(true);
        });
    });

    describe('setState', () => {
        test('setState_givenAStateWithoutWhatTheLastOneHad_undoesIt', () => {
            // Arrange
            const {map, manager, enemies} = arrangeMap({
                mapContextState: {killed: [], in_combat: [1], obsolete: [2], overpulled: [[3, 7]], players: []},
            });
            map.signal('map:mapobjectgroupsloaded');
            expect(enemies[1].isInCombat()).toBe(true);
            expect(enemies[2].isObsolete()).toBe(true);
            expect(enemies[3].getOverpulledKillZoneId()).toBe(7);

            // Act
            manager.setState({killed: [], in_combat: [], obsolete: [], overpulled: [], players: []});

            // Assert
            expect(enemies[1].isInCombat()).toBe(false);
            expect(enemies[2].isObsolete()).toBe(false);
            expect(enemies[3].getOverpulledKillZoneId()).toBeNull();
        });

        test('setState_givenTheSameStateTwice_signalsNoChangeTheSecondTime', () => {
            // Arrange
            const state = {killed: [1], in_combat: [2], obsolete: [3], overpulled: [[1, 7]], players: []};
            const {map, manager, enemies} = arrangeMap();
            map.signal('map:mapobjectgroupsloaded');
            manager.setState(state);
            const changes = [];
            for (const enemy of Object.values(enemies)) {
                enemy.register(['killed:changed', 'incombat:changed', 'obsolete:changed', 'overpulled:changed'], changes, (event) => {
                    changes.push(`${event.context.id}:${event.name}`);
                });
            }

            // Act
            manager.setState(state);

            // Assert
            expect(changes).toEqual([]);
            expect(enemies[1].isKilled()).toBe(true);
            expect(enemies[1].getOverpulledKillZoneId()).toBe(7);
        });

        test('setState_givenAnEnemyOverpulledInAnotherPull_tellsThatPullBeforeMovingTheEnemy', () => {
            // Arrange
            const {map, manager, killZones, calls} = arrangeMap({
                mapContextState: {killed: [], in_combat: [], obsolete: [], overpulled: [[2, 7]], players: []},
            });
            map.signal('map:mapobjectgroupsloaded');
            calls.length = 0;

            // Act
            manager.setState({killed: [], in_combat: [], obsolete: [], overpulled: [[2, 8]], players: []});

            // Assert
            expect(calls.filter(call => call.includes('2'))).toEqual([
                'kz8.addOverpulledEnemy(2)',
                'enemy2.setOverpulledKillZoneId(8)',
            ]);
            expect(killZones[8].overpulledEnemies).toEqual([2]);
        });

        test('setState_givenPlayers_placesThemAtTheirSplitFloorPosition', () => {
            // Arrange
            const {map, manager, playerPositionMapObjectGroup} = arrangeMap({facadeEnabled: true, facadeStyle: 'split_floors'});
            map.signal('map:mapobjectgroupsloaded');

            // Act
            manager.setState({killed: [], in_combat: [], obsolete: [], overpulled: [], players: [player]});

            // Assert
            expect(playerPositionMapObjectGroup.createOrUpdatePlayerPosition).toHaveBeenCalledWith(
                'Player-1084-0A6BB627', 'Herochar', -10.5, 20.25, 3, 7, 262, 'https://example.test/262.png'
            );
        });

        test('setState_givenPlayersAndAViewerOnTheFacade_placesThemAtTheirFacadePosition', () => {
            // Arrange
            const {map, manager, playerPositionMapObjectGroup} = arrangeMap({facadeEnabled: true, facadeStyle: 'facade'});
            map.signal('map:mapobjectgroupsloaded');

            // Act
            manager.setState({killed: [], in_combat: [], obsolete: [], overpulled: [], players: [player]});

            // Assert
            expect(playerPositionMapObjectGroup.createOrUpdatePlayerPosition).toHaveBeenCalledWith(
                'Player-1084-0A6BB627', 'Herochar', -50, 60, 9, 7, 262, 'https://example.test/262.png'
            );
        });

        test('setState_givenAFacadeStyleOnADungeonWithoutFacade_placesPlayersAtTheirSplitFloorPosition', () => {
            // Arrange
            const {map, manager, playerPositionMapObjectGroup} = arrangeMap({facadeEnabled: false, facadeStyle: 'facade'});
            map.signal('map:mapobjectgroupsloaded');

            // Act
            manager.setState({killed: [], in_combat: [], obsolete: [], overpulled: [], players: [player]});

            // Assert
            expect(playerPositionMapObjectGroup.createOrUpdatePlayerPosition).toHaveBeenCalledWith(
                'Player-1084-0A6BB627', 'Herochar', -10.5, 20.25, 3, 7, 262, 'https://example.test/262.png'
            );
        });
    });

    describe('while picking overpulled enemies', () => {
        test('setState_givenAnOverpullSelectionInProgress_appliesTheStateOnlyOnceItEndsWithoutChanges', () => {
            // Arrange
            const {map, manager, enemies} = arrangeMap();
            map.signal('map:mapobjectgroupsloaded');
            const selection = new SelectKillZoneEnemySelectionOverpull(false);
            map.mapState = selection;

            // Act
            manager.setState({killed: [1], in_combat: [], obsolete: [], overpulled: [], players: []});
            const isKilledDuringSelection = enemies[1].isKilled();
            map.mapState = null;
            map.signal('map:mapstatechanged', {previousMapState: selection, newMapState: null});

            // Assert
            expect(isKilledDuringSelection).toBe(false);
            expect(enemies[1].isKilled()).toBe(true);
        });

        test('mapstatechanged_givenAnOverpullSelectionEndedWithPicksToSave_leavesThePicksOnScreen', () => {
            // Arrange
            const {map, manager, enemies} = arrangeMap();
            map.signal('map:mapobjectgroupsloaded');
            const selection = new SelectKillZoneEnemySelectionOverpull(true);
            map.mapState = selection;
            manager.setState({killed: [], in_combat: [], obsolete: [], overpulled: [], players: []});
            enemies[2].setOverpulledKillZoneId(7);

            // Act
            map.mapState = null;
            map.signal('map:mapstatechanged', {previousMapState: selection, newMapState: null});

            // Assert
            expect(enemies[2].getOverpulledKillZoneId()).toBe(7);
        });
    });
});
