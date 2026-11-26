// Picking overpulled enemies in a live session. EnemySelection is stubbed down to the members the selection uses;
// the enemies are the real LiveSessionEnemy on a stub Enemy base, so their overpulled state behaves as in the app.

const {Signalable} = require('../../signalable.js');
global.Signalable = Signalable;
global.Enemy = class Enemy extends Signalable {
};
global.KillZone = class KillZone {
};
global.EnemySelection = class EnemySelection extends Signalable {
    constructor(map, sourceMapObject) {
        super();
        this.map = map;
        this.sourceMapObject = sourceMapObject;
    }

    stop() {
    }

    cleanup() {
    }
};

const {LiveSessionEnemy} = require('../../models/livesessionenemy.js');
global.LiveSessionEnemy = LiveSessionEnemy;
const {SelectKillZoneEnemySelectionOverpull} = require('./selectkillzoneenemyselectionoverpull.js');

function makeEnemy(id, overpulledKillZoneId = null) {
    const enemy = new LiveSessionEnemy(null, null);
    enemy.id = id;
    enemy.overpulledKillZoneId = overpulledKillZoneId;
    return enemy;
}

function arrangeSelection() {
    const killZone = {id: 7, addOverpulledEnemy: vi.fn()};
    const enemies = {1: makeEnemy(1), 2: makeEnemy(2, 7)};
    const map = {
        liveSessionStateManager: {applyCurrentState: vi.fn()},
        mapObjectGroupManager: {getEnemyMapObjectGroup: () => ({objects: enemies})},
    };
    const ajaxRequests = [];
    global.$.ajax = (request) => ajaxRequests.push(request);
    global.getState = () => ({
        getMapContext: () => ({getPublicKey: () => 'route', getLiveSessionPublicKey: () => 'session'}),
    });

    return {selection: new SelectKillZoneEnemySelectionOverpull(map, killZone), killZone, enemies, map, ajaxRequests};
}

describe('SelectKillZoneEnemySelectionOverpull', () => {
    test('toggleOverpulled_givenAnEnemyThatIsNotOverpulled_overpullsItInTheSelectedPull', () => {
        // Arrange
        const {selection, killZone, enemies} = arrangeSelection();

        // Act
        selection.toggleOverpulled(enemies[1]);

        // Assert
        expect(killZone.addOverpulledEnemy).toHaveBeenCalledWith(enemies[1]);
        expect(enemies[1].getOverpulledKillZoneId()).toBe(7);
        expect(selection.hasChanges()).toBe(true);
    });

    test('toggleOverpulled_givenAnOverpulledEnemy_unmarksIt', () => {
        // Arrange
        const {selection, killZone, enemies} = arrangeSelection();

        // Act
        selection.toggleOverpulled(enemies[2]);

        // Assert
        expect(killZone.addOverpulledEnemy).not.toHaveBeenCalled();
        expect(enemies[2].getOverpulledKillZoneId()).toBeNull();
    });

    test('stop_givenNothingPicked_savesNothing', () => {
        // Arrange
        const {selection, ajaxRequests} = arrangeSelection();

        // Act
        selection.stop();

        // Assert
        expect(selection.hasChanges()).toBe(false);
        expect(ajaxRequests).toEqual([]);
    });

    test('stop_givenPicksTheServerStateOverwroteOnScreen_savesThePicks', () => {
        // Arrange
        const {selection, enemies, ajaxRequests} = arrangeSelection();
        selection.toggleOverpulled(enemies[1]);
        selection.toggleOverpulled(enemies[2]);
        enemies[1].setOverpulledKillZoneId(null);
        enemies[2].setOverpulledKillZoneId(7);

        // Act
        selection.stop();
        ajaxRequests[0].success({});

        // Assert
        expect(ajaxRequests.map(request => request.type)).toEqual(['DELETE', 'POST']);
        expect(ajaxRequests[0].url).toBe('/ajax/route/live/session/overpulledenemy');
        expect(ajaxRequests[0].data).toEqual({kill_zone_id: 7, enemy_ids: [2], no_result: 1});
        expect(ajaxRequests[1].data).toEqual({kill_zone_id: 7, enemy_ids: [1]});
    });

    test('stop_givenTheSaveFailed_showsTheLiveSessionStateAgain', () => {
        // Arrange
        const {selection, enemies, map, ajaxRequests} = arrangeSelection();
        selection.toggleOverpulled(enemies[1]);

        // Act
        selection.stop();
        ajaxRequests[0].error();

        // Assert
        expect(map.liveSessionStateManager.applyCurrentState).toHaveBeenCalledTimes(1);
    });

    test('stop_givenTheSaveSucceeded_marksTheRouteCorrectionsObsoleteEnemies', () => {
        // Arrange
        const {selection, enemies, ajaxRequests} = arrangeSelection();
        selection.toggleOverpulled(enemies[1]);

        // Act
        selection.stop();
        ajaxRequests[0].success({obsolete_enemy_ids: [2], enemy_forces: 100});

        // Assert
        expect(enemies[2].isObsolete()).toBe(true);
        expect(enemies[1].isObsolete()).toBe(false);
    });
});
