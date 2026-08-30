// ---------------------------------------------------------------------------
// Regression coverage for #4376: MapContextMappingVersion.addRawNpc()/removeRawNpcById()
// read/wrote `this._options.npcs`, but every reader (MapContext.findNpcById()/getNpcs())
// reads `this._options.dungeonNpcs` instead. `_options.npcs` was never initialized anywhere,
// so removeRawNpcById()'s `for...in` over `undefined` silently no-op'd, and addRawNpc()'s
// `.push` on `undefined` threw a TypeError - uncaught inside the live NPC-update event handler,
// aborting the rest of that handler (including the enemy redraw loop) whenever the npc-added
// branch was reached. See the recipe at the top of ../models/killzone.test.js: fake the base
// class instead of constructing the real (heavy) MapContext.
// ---------------------------------------------------------------------------

global.MapContext = class MapContext {
    constructor(options) {
        this._options = options;
    }

    signal(event, data) {
        this._signals = this._signals ?? [];
        this._signals.push({event, data});
    }
};

// Referenced only inside `console.assert(this instanceof MapContextMappingVersionEdit, ...)` -
// a bare stub keeps that identifier lookup from throwing a ReferenceError.
global.MapContextMappingVersionEdit = class MapContextMappingVersionEdit {
};

const {MapContextMappingVersion} = require('./mapcontextmappingversion');

// The class' own methods assert `this instanceof MapContextMappingVersionEdit` via
// console.assert - harmless in a test (it only logs), so a plain instance is fine here.
function makeMapContext(options) {
    return new MapContextMappingVersion(options);
}

describe('MapContextMappingVersion', () => {
    test('addRawNpc_givenNewNpc_pushesToDungeonNpcsNotNpcs', () => {
        // Arrange
        let mapContext = makeMapContext({dungeonNpcs: [], npcEnemyForces: []});

        // Act
        mapContext.addRawNpc({id: 42, name: 'Test Npc'});

        // Assert
        expect(mapContext._options.dungeonNpcs).toHaveLength(1);
        expect(mapContext._options.dungeonNpcs[0].id).toBe(42);
        expect(mapContext._options.npcs).toBeUndefined();
    });

    test('addRawNpc_givenMatchingNpcEnemyForcesEntry_enrichesTheNpcWithEnemyForces', () => {
        // Arrange
        let mapContext = makeMapContext({
            dungeonNpcs:     [],
            npcEnemyForces: [{id: 42, enemy_forces: 7}],
        });

        // Act
        mapContext.addRawNpc({id: 42, name: 'Test Npc'});

        // Assert
        expect(mapContext._options.dungeonNpcs[0].enemy_forces).toBe(7);
    });

    test('removeRawNpcById_givenExistingNpc_removesItFromDungeonNpcs', () => {
        // Arrange
        let mapContext = makeMapContext({
            dungeonNpcs:    [{id: 1, name: 'Keep'}, {id: 42, name: 'Remove'}],
            npcEnemyForces: [],
        });

        // Act
        mapContext.removeRawNpcById(42);

        // Assert
        expect(mapContext._options.dungeonNpcs).toHaveLength(1);
        expect(mapContext._options.dungeonNpcs[0].id).toBe(1);
    });

    test('removeRawNpcById_givenUnknownNpcId_leavesDungeonNpcsUntouched', () => {
        // Arrange
        let mapContext = makeMapContext({
            dungeonNpcs:    [{id: 1, name: 'Keep'}],
            npcEnemyForces: [],
        });

        // Act
        mapContext.removeRawNpcById(999);

        // Assert
        expect(mapContext._options.dungeonNpcs).toHaveLength(1);
    });
});
