// ---------------------------------------------------------------------------
// Regression coverage for #4376. Bugs in NpcChangedHandler.onReceive():
//
// 1. Relevance used to be decided from `e.model.dungeon_id`, a legacy column
//    NpcController never writes, instead of the explicit `removed_from_dungeon`
//    flag the backend now sends. A false-negative there called `enemy.setNpc(null)`,
//    which nulls the enemy's real `npc_id` (not just a display value) - the "?" icon,
//    and a real lost connection if the enemy is saved afterward.
// 2. Even on the "still belongs" path, `enemy.setNpc(e.model)` would zero the enemy's
//    `enemy_forces`/`enemy_forces_teeming`, because the live-update payload's npc can
//    never carry the right value (NpcEnemyForces is scoped per mapping version, not
//    per dungeon). The handler must preserve whatever the enemy already had.
// 3. When an admin renames an npc's id, the enemies/npcs still holding the old id in
//    connected clients' in-memory state were never told, since the broadcast only
//    carried the new id and the handler matched on `e.model.id` alone (the DB rows
//    were already remapped server-side). `e.old_npc_id` fixes this.
//
// Following the recipe at the top of ../../../../models/killzone.test.js: fake the
// base class (MessageHandler) and collaborators instead of requiring the real chain.
// ---------------------------------------------------------------------------

global.MAP_OBJECT_GROUP_ENEMY = 'enemy';

global.MessageHandler = class MessageHandler {
    constructor(echo, message) {
        this.echo = echo;
        this.message = message;
    }

    // The real implementation's own logic (message re-mapping, echoUser floor tracking) is
    // exercised elsewhere; here it just needs to exist and not throw.
    onReceive(e) {
    }
};

global.NpcChangedMessage = class NpcChangedMessage {
    static getName() {
        return 'npc-changed';
    }
};

const {NpcChangedHandler} = require('./changed');

function makeEnemy(npcId, enemyForces, enemyForcesTeeming) {
    return {
        npc_id:             npcId,
        enemy_forces:       enemyForces,
        enemy_forces_teeming: enemyForcesTeeming,
        setNpc(npc) {
            this.npc = npc;
            this.npc_id = npc === null ? null : npc.id;
            // Mirror enemy.js's own setNpc() fallback so this fake reproduces the bug it's guarding against
            if (npc !== null && (npc.enemy_forces === null || typeof npc.enemy_forces === 'undefined')) {
                this.enemy_forces = 0;
                this.enemy_forces_teeming = null;
            }
        },
        setSynced: vi.fn(),
        visual:    {refresh: vi.fn()},
    };
}

function makeMapContext() {
    return {
        removeRawNpcById: vi.fn(),
        addRawNpc:        vi.fn(),
    };
}

function makeHandler(mapContext, enemies) {
    global.getState = () => ({getMapContext: () => mapContext});

    let handler = new NpcChangedHandler({
        map: {
            mapObjectGroupManager: {
                getByName: () => ({objects: enemies}),
            },
        },
    });

    return handler;
}

describe('NpcChangedHandler', () => {
    test('onReceive_givenNpcStillBelongsToDungeon_reassignsMatchingEnemiesAndAddsNpc', () => {
        // Arrange
        let enemy = makeEnemy(42, 5, null);
        let mapContext = makeMapContext();
        let handler = makeHandler(mapContext, {1: enemy});
        let npc = {id: 42, name: 'Updated Npc'};

        // Act
        handler.onReceive({model: npc, removed_from_dungeon: false});

        // Assert
        expect(mapContext.removeRawNpcById).toHaveBeenCalledWith(42);
        expect(mapContext.addRawNpc).toHaveBeenCalledWith(npc);
        expect(enemy.npc).toBe(npc);
        expect(enemy.npc_id).toBe(42);
        expect(enemy.visual.refresh).toHaveBeenCalled();
        expect(enemy.setSynced).toHaveBeenCalledWith(true);
    });

    test('onReceive_givenNpcStillBelongsToDungeon_preservesTheEnemysExistingEnemyForces', () => {
        // Arrange - the broadcast npc carries no enemy_forces (it can't - NpcEnemyForces is
        // scoped per mapping version, not per dungeon), so the enemy's existing values must survive.
        let enemy = makeEnemy(42, 5, 3);
        let mapContext = makeMapContext();
        let handler = makeHandler(mapContext, {1: enemy});
        let npc = {id: 42, name: 'Updated Npc'};

        // Act
        handler.onReceive({model: npc, removed_from_dungeon: false});

        // Assert
        expect(enemy.enemy_forces).toBe(5);
        expect(enemy.enemy_forces_teeming).toBe(3);
    });

    test('onReceive_givenNpcRemovedFromDungeon_unassignsMatchingEnemiesWithoutAddingTheNpc', () => {
        // Arrange
        let enemy = makeEnemy(42, 5, null);
        let mapContext = makeMapContext();
        let handler = makeHandler(mapContext, {1: enemy});
        let npc = {id: 42, name: 'Updated Npc'};

        // Act
        handler.onReceive({model: npc, removed_from_dungeon: true});

        // Assert
        expect(mapContext.removeRawNpcById).toHaveBeenCalledWith(42);
        expect(mapContext.addRawNpc).not.toHaveBeenCalled();
        expect(enemy.npc).toBeNull();
        expect(enemy.npc_id).toBeNull();
    });

    test('onReceive_givenEnemyAssignedToDifferentNpc_leavesItUntouched', () => {
        // Arrange
        let enemy = makeEnemy(1, 5, null);
        let mapContext = makeMapContext();
        let handler = makeHandler(mapContext, {1: enemy});
        let npc = {id: 42, name: 'Updated Npc'};

        // Act
        handler.onReceive({model: npc, removed_from_dungeon: false});

        // Assert
        expect(enemy.npc).toBeUndefined();
        expect(enemy.npc_id).toBe(1);
        expect(enemy.setSynced).toHaveBeenCalledWith(true);
    });

    test('onReceive_givenNpcRenamedToNewId_reassignsEnemiesStillHoldingTheOldIdAndRemovesTheOldRawNpc', () => {
        // Arrange - the DB rows were already remapped from 42 to 99 server-side; this client's
        // enemy and raw npc list still reference the old id 42.
        let enemy = makeEnemy(42, 5, null);
        let mapContext = makeMapContext();
        let handler = makeHandler(mapContext, {1: enemy});
        let npc = {id: 99, name: 'Renamed Npc'};

        // Act
        handler.onReceive({model: npc, removed_from_dungeon: false, old_npc_id: 42});

        // Assert
        expect(mapContext.removeRawNpcById).toHaveBeenCalledWith(99);
        expect(mapContext.removeRawNpcById).toHaveBeenCalledWith(42);
        expect(mapContext.addRawNpc).toHaveBeenCalledWith(npc);
        expect(enemy.npc).toBe(npc);
        expect(enemy.npc_id).toBe(99);
    });
});
