// ---------------------------------------------------------------------------
// KillZone.save() round trips against the real MapObject.save(), with $.ajax captured so each
// test decides when, and with what, a request answers.
//
// Follows the global-script model recipe documented at the top of killzone.test.js, except that
// the base class is the real MapObject (built on a small Signalable stand-in, as in
// mapobject.test.js): the save response handling under test lives in MapObject.save().
// ---------------------------------------------------------------------------

global.MAP_OBJECT_GROUP_ENEMY = 'enemy';
global.MAP_OBJECT_GROUP_KILLZONE = 'killzone';

global._ = require('lodash');
global.isNumeric = require('../util').isNumeric;

global.Attribute = class Attribute {
    constructor(options) {
        this.options = options;
        Object.assign(this, options);
    }

    isEditableAdmin() {
        return !this.options.hasOwnProperty('admin') || this.options.admin === getState().isMapAdmin();
    }

    isSaveable() {
        return this.options.hasOwnProperty('save') ? this.options.save : true;
    }
};

// Listeners receive the `{name, context, data}` wrapper, as with the real signalable.js.
global.Signalable = class Signalable {
    constructor() {
        this._signals = [];
    }

    register(names, listener, fn) {
        for (const name of Array.isArray(names) ? names : [names]) {
            this._signals.push({name, listener, callback: fn});
        }
    }

    unregister(names, listener) {
        for (const name of Array.isArray(names) ? names : [names]) {
            this._signals = this._signals.filter((caller) => !(caller.name === name && caller.listener === listener));
        }
    }

    signal(name, data = {}) {
        for (const caller of [...this._signals]) {
            if (caller.name === name) {
                caller.callback({name: name, context: this, data: data});
            }
        }
    }

    _cleanupSignals() {
        this._signals = [];
    }

    cleanup() {
    }
};

global.DungeonMap = class DungeonMap {
};
global.Enemy = class Enemy {
};
global.EnemySelection = class EnemySelection {
};
global.MapContextLiveSession = class MapContextLiveSession {
};
global.MapContextDungeonRoute = class MapContextDungeonRoute {
};
global.SelectKillZoneEnemySelectionOverpull = class SelectKillZoneEnemySelectionOverpull {
};
global.defaultAjaxErrorFn = () => {
};

global.c = {map: {killzone: {defaultColor: () => '#ff0000'}}};

global.L = {
    divIcon: () => ({}),
    Marker: {extend: () => function () {}},
    Draw: {
        Marker: {extend: () => function () {}},
        Feature: {prototype: {initialize() {}}},
    },
};

const fakeState = {
    register: () => {},
    unregister: () => {},
    isMapAdmin: () => false,
    getCurrentFloor: () => ({id: 1}),
    getMapContext: () => ({
        register: () => {},
        unregister: () => {},
        getPublicKey: () => 'abcdefg',
    }),
};
global.getState = () => fakeState;

/** @type {Object[]} Every $.ajax() call, with its data serialized at call time like jQuery does. */
let ajaxRequests = [];

global.$ = Object.assign(() => ({attr() {}, removeAttr() {}}), {
    fn: {},
    inArray: (value, array) => (Array.isArray(array) ? array.indexOf(value) : -1),
    each: (obj, callback) => Object.keys(obj ?? {}).forEach((key) => callback(key, obj[key])),
    ajax: (options) => ajaxRequests.push({...options, sentData: JSON.parse(JSON.stringify(options.data))}),
});

const {MapObject} = require('./mapobject');
global.MapObject = MapObject;
const {KillZone} = require('./killzone');
const {fakeMapObjectGroupManager} = require('#test/fixtures/mapObjectGroupManager');

/**
 * A fake enemy that, like the real Enemy.setKillZone(), signals killzone:detached to the pull it leaves.
 *
 * @param {Number} id
 * @returns {Enemy}
 */
function makeFakeEnemy(id) {
    const listeners = [];

    return Object.assign(new Enemy(), {
        id,
        floor_id: 2,
        enemy_pack_id: null,
        _killZone: null,
        setKillZone(killZone) {
            const previous = this._killZone;
            this._killZone = killZone;

            if (previous !== null && (killZone === null || previous.id !== killZone.id)) {
                listeners.filter((listener) => listener.name === 'killzone:detached')
                    .forEach((listener) => listener.fn({context: this, data: {previous: previous}}));
            }
        },
        getKillZone() {
            return this._killZone;
        },
        register(name, listener, fn) {
            listeners.push({name, listener, fn});
        },
        unregister(name, listener) {
            listeners.splice(0, listeners.length,
                ...listeners.filter((candidate) => !(candidate.name === name && candidate.listener === listener)));
        },
        isAwakenedNpc: () => false,
        isLinkedToLastBoss: () => false,
        isObsolete: () => false,
        shouldBeIgnored: () => false,
        getEnemyForces: () => 10,
    });
}

/**
 * A saved pull holding enemies 1-3, out of enemies 1-5 that exist on the map.
 *
 * @returns {{killZone: KillZone, enemies: Object}}
 */
function makeSavedKillZone() {
    const enemies = {};
    [1, 2, 3, 4, 5].forEach((id) => (enemies[id] = makeFakeEnemy(id)));

    const enemyGroup = {
        register: () => {},
        unregister: () => {},
        findMapObjectById: (id) => enemies[id] ?? null,
        setMapObjectVisibility: () => {},
        objects: enemies,
    };
    const genericGroup = {register: () => {}, unregister: () => {}, findMapObjectById: () => null};

    const map = Object.assign(new DungeonMap(), {
        options: {edit: true, noUI: true},
        register: () => {},
        unregister: () => {},
        leafletMap: {closePopup: () => {}, removeLayer: () => {}},
        mapObjectGroupManager: fakeMapObjectGroupManager((name) => name === MAP_OBJECT_GROUP_ENEMY ? enemyGroup : genericGroup),
    });

    const killZone = new KillZone(map, null);
    killZone.id = 5;
    killZone.floor_id = 1;
    killZone.redrawConnectionsToEnemies = vi.fn();
    [1, 2, 3].forEach((id) => killZone._addEnemy(enemies[id]));
    killZone.setSynced(true);

    return {killZone, enemies};
}

/**
 * Answers a captured request the way the server does: with the pull as it was sent.
 *
 * @param {Object} request
 * @param {Object} overrides
 */
function respondWithSentKillZone(request, overrides = {}) {
    request.success({...request.sentData, enemy_forces: 30, ...overrides});
    request.complete();
}

/**
 * @param {Object} request
 */
function respondWithServerError(request) {
    request.error({status: 500}, 'error', 'Internal Server Error');
    request.complete();
}

const sorted = (ids) => [...ids].sort((a, b) => a - b);

beforeEach(() => {
    ajaxRequests = [];
});

describe('KillZone.save', () => {
    it('save_givenNoLocalChangeWhileInFlight_appliesTheResponseEnemies', () => {
        // Arrange
        const {killZone} = makeSavedKillZone();
        killZone.save();

        // Act
        respondWithSentKillZone(ajaxRequests[0], {enemies: [1, 2]});

        // Assert
        expect(sorted(killZone.enemies)).toEqual([1, 2]);
        expect(ajaxRequests).toHaveLength(1);
    });

    it('save_givenEnemyAddedWhileInFlight_keepsTheAddedEnemy', () => {
        // Arrange
        const {killZone, enemies} = makeSavedKillZone();
        killZone.save();
        killZone._addEnemy(enemies[4]);

        // Act
        respondWithSentKillZone(ajaxRequests[0]);

        // Assert
        expect(sorted(killZone.enemies)).toEqual([1, 2, 3, 4]);
        expect(enemies[4].getKillZone()).toBe(killZone);
    });

    it('save_givenEnemyRemovedWhileInFlight_keepsTheEnemyRemoved', () => {
        // Arrange
        const {killZone, enemies} = makeSavedKillZone();
        killZone.save();
        killZone._removeEnemy(enemies[2]);

        // Act
        respondWithSentKillZone(ajaxRequests[0]);

        // Assert
        expect(sorted(killZone.enemies)).toEqual([1, 3]);
        expect(enemies[2].getKillZone()).toBeNull();
    });

    it('save_givenEnemyAddedWhileInFlight_savesTheNewerEnemiesAgain', () => {
        // Arrange
        const {killZone, enemies} = makeSavedKillZone();
        killZone.save();
        killZone._addEnemy(enemies[4]);

        // Act
        respondWithSentKillZone(ajaxRequests[0]);

        // Assert
        expect(ajaxRequests).toHaveLength(2);
        expect(ajaxRequests[1].type).toBe('PUT');
        expect(sorted(ajaxRequests[1].sentData.enemies)).toEqual([1, 2, 3, 4]);
    });

    it('save_givenOverlappingSavesAndOlderAnsweredFirst_keepsTheEnemyAddedBetweenThem', () => {
        // Arrange
        const {killZone, enemies} = makeSavedKillZone();
        killZone.save();
        killZone._addEnemy(enemies[4]);
        killZone.save();
        const [olderSave] = ajaxRequests;

        // Act
        respondWithSentKillZone(olderSave);

        // Assert
        expect(sorted(killZone.enemies)).toEqual([1, 2, 3, 4]);
    });

    it('save_givenOverlappingSavesAndNewerAnsweredFirst_keepsTheEnemyAddedBetweenThem', () => {
        // Arrange
        const {killZone, enemies} = makeSavedKillZone();
        killZone.save();
        killZone._addEnemy(enemies[4]);
        killZone.save();
        const [olderSave, newerSave] = ajaxRequests;

        // Act
        respondWithSentKillZone(newerSave);
        respondWithSentKillZone(olderSave);

        // Assert
        expect(sorted(killZone.enemies)).toEqual([1, 2, 3, 4]);
    });

    it('save_givenNewerSaveFailsAndOlderSaveSucceedsLate_keepsAndResavesTheNewerEnemies', () => {
        // Arrange
        const {killZone, enemies} = makeSavedKillZone();
        killZone.save();
        killZone._addEnemy(enemies[4]);
        killZone.save();
        const [olderSave, newerSave] = ajaxRequests;

        // Act
        respondWithServerError(newerSave);
        respondWithSentKillZone(olderSave);

        // Assert
        expect(sorted(killZone.enemies)).toEqual([1, 2, 3, 4]);
        expect(ajaxRequests).toHaveLength(3);
        expect(sorted(ajaxRequests[2].sentData.enemies)).toEqual([1, 2, 3, 4]);
    });

    it('save_givenPullDeletedWhileInFlight_doesNotSaveItAgain', () => {
        // Arrange
        const {killZone} = makeSavedKillZone();
        killZone.save();
        // Detaching from its enemies changes the pull's enemies too
        killZone.localDelete();

        // Act
        respondWithSentKillZone(ajaxRequests[0]);

        // Assert
        expect(killZone.enemies).toEqual([]);
        expect(ajaxRequests).toHaveLength(1);
    });
});
