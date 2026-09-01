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
