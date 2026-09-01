// Coverage for EnemyVisualMain#isSpeedrunRequiredNpc (#4406) - shared by the `required_npc`
// border class in _getTemplateData() and by EnemyVisualMainEnemyForces, which needs the same
// check to know when to show `1` instead of the enemy forces value.
//
// Follows the load-time-stub recipe from enemyvisual.test.js: the class hierarchy this file
// extends is stubbed out globally, and the method under test is called off the prototype with a
// fake `this` rather than going through the full constructor chain.

global.Signalable = class Signalable {
};
global.EnemyVisual = class EnemyVisual {
};
global.EnemyVisualIcon = class EnemyVisualIcon extends Signalable {
    constructor(enemyvisual) {
        super();
        this.enemyvisual = enemyvisual;
    }
};

const {EnemyVisualMain} = require('./enemyvisualmain');

class FakeMapContextDungeonRoute {
    constructor({difficulty = 'mythic_plus', requiredNpcs = []} = {}) {
        this._difficulty = difficulty;
        this._requiredNpcs = requiredNpcs;
    }

    getDungeonDifficulty() {
        return this._difficulty;
    }

    getDungeonSpeedrunRequiredNpcs() {
        return this._requiredNpcs;
    }
}

global.MapContextDungeonRoute = FakeMapContextDungeonRoute;

function makeFakeThis({npc = {}, npcId = 123, mapContext} = {}) {
    global.getState = () => ({
        getMapContext: () => mapContext,
    });

    return {
        enemyvisual: {
            enemy: {
                npc,
                npc_id: npcId,
            },
        },
    };
}

test('isSpeedrunRequiredNpc_givenNpcInRequiredList_returnsTrue', () => {
    const mapContext = new FakeMapContextDungeonRoute({
        requiredNpcs: [
            {dungeon_speedrun_required_npc_npcs: [{npc_id: 999}]},
            {dungeon_speedrun_required_npc_npcs: [{npc_id: 123}]},
        ],
    });
    const fakeThis = makeFakeThis({npcId: 123, mapContext});

    expect(EnemyVisualMain.prototype.isSpeedrunRequiredNpc.call(fakeThis)).toBe(true);
});

test('isSpeedrunRequiredNpc_givenNpcNotInRequiredList_returnsFalse', () => {
    const mapContext = new FakeMapContextDungeonRoute({
        requiredNpcs: [
            {dungeon_speedrun_required_npc_npcs: [{npc_id: 999}]},
        ],
    });
    const fakeThis = makeFakeThis({npcId: 123, mapContext});

    expect(EnemyVisualMain.prototype.isSpeedrunRequiredNpc.call(fakeThis)).toBe(false);
});

test('isSpeedrunRequiredNpc_givenNoNpc_returnsFalse', () => {
    const mapContext = new FakeMapContextDungeonRoute({
        requiredNpcs: [{dungeon_speedrun_required_npc_npcs: [{npc_id: 123}]}],
    });
    const fakeThis = makeFakeThis({npc: null, npcId: 123, mapContext});

    expect(EnemyVisualMain.prototype.isSpeedrunRequiredNpc.call(fakeThis)).toBe(false);
});

test('isSpeedrunRequiredNpc_givenMapContextIsNotDungeonRoute_returnsFalse', () => {
    const fakeThis = makeFakeThis({npcId: 123, mapContext: {}});

    expect(EnemyVisualMain.prototype.isSpeedrunRequiredNpc.call(fakeThis)).toBe(false);
});

test('isSpeedrunRequiredNpc_givenNoDungeonDifficulty_returnsFalse', () => {
    const mapContext = new FakeMapContextDungeonRoute({
        difficulty: null,
        requiredNpcs: [{dungeon_speedrun_required_npc_npcs: [{npc_id: 123}]}],
    });
    const fakeThis = makeFakeThis({npcId: 123, mapContext});

    expect(EnemyVisualMain.prototype.isSpeedrunRequiredNpc.call(fakeThis)).toBe(false);
});

// ---------------------------------------------------------------------------
// getSize()'s cache is keyed on the zoom level. With zoomSnap: 0 (constants.js), the map lands on
// fractional zoom levels under real mouse-wheel zooming, so the key must be quantized to the
// nearest half-zoom-level step (the offset's existing 1px granularity) - otherwise the cache
// never hits during ordinary zooming and grows unbounded, one entry per distinct zoom level ever
// visited (#4413).
// ---------------------------------------------------------------------------

global.c = {
    map: {
        enemy: {
            calculateMargin: () => 4,
            calculateSize: () => 20,
            mdt_size_factor: 0.5,
            boss_size_factor: 2,
        },
    },
};
global.NPC_CLASSIFICATION_ID_BOSS = 'boss';
global.NPC_CLASSIFICATION_ID_FINAL_BOSS = 'final_boss';

function makeFakeSizeThis(zoomLevel) {
    global.getState = () => ({
        getMapZoomLevel: () => zoomLevel,
        getMapContext: () => ({
            getNpcHealth: () => 100,
            getNpcsMinHealth: () => 0,
            getNpcsMaxHealth: () => 1000,
        }),
        isMapAdmin: () => true,
    });

    const fakeThis = Object.create(EnemyVisualMain.prototype);
    fakeThis._sizeCache = [];
    fakeThis.enemyvisual = {
        enemy: {
            npc: {dungeon_id: 1, classification_id: null},
            is_mdt: false,
        },
    };

    return fakeThis;
}

test('getSize_givenFractionalZoomLevelsRoundingToSameStep_hitsCache', () => {
    const fakeThis = makeFakeSizeThis(4);
    const first = EnemyVisualMain.prototype.getSize.call(fakeThis);

    global.getState = () => ({
        getMapZoomLevel: () => 4.1,
        // Deliberately broken - if this were reached (cache miss) the call would throw.
        getMapContext: () => {
            throw new Error('getSize() should not recalculate on a cache hit');
        },
        isMapAdmin: () => true,
    });
    const second = EnemyVisualMain.prototype.getSize.call(fakeThis);

    expect(second).toBe(first);
    expect(Object.keys(fakeThis._sizeCache)).toHaveLength(1);
});

test('getSize_givenFractionalZoomLevelsRoundingToDifferentSteps_missesCache', () => {
    const fakeThis = makeFakeSizeThis(4.1);
    EnemyVisualMain.prototype.getSize.call(fakeThis);

    global.getState = () => ({
        getMapZoomLevel: () => 4.9,
        getMapContext: () => ({
            getNpcHealth: () => 100,
            getNpcsMinHealth: () => 0,
            getNpcsMaxHealth: () => 1000,
        }),
        isMapAdmin: () => true,
    });
    EnemyVisualMain.prototype.getSize.call(fakeThis);

    expect(Object.keys(fakeThis._sizeCache)).toHaveLength(2);
});

test('constructor_givenEnemySetNpcSignal_clearsSizeCache', () => {
    let registeredCallback = null;
    const enemyvisual = {
        enemy: {
            register: (signal, context, callback) => {
                if (signal === 'enemy:set_npc') {
                    registeredCallback = callback;
                }
            },
        },
    };

    const visual = new EnemyVisualMain(enemyvisual);
    visual._sizeCache[8] = {iconSize: [20, 20]};

    registeredCallback();

    expect(visual._sizeCache).toEqual([]);
});
