// ---------------------------------------------------------------------------
// KillZoneMapObjectGroup.hasKilledAllRequiredEnemies() decides whether the route editor lets the user publish
// (#3666). Its teeming branch checked `enemy.teeming === 'invisible'`, but that column only ever holds null,
// 'visible' or 'hidden' (Enemy::TEEMING_ALL) - so an enemy that despawns on teeming keys was still demanded of a
// teeming route, and the branch meant to excuse it was dead. These tests pin all three teeming values against both
// route teeming settings.
// ---------------------------------------------------------------------------

const {MAP_OBJECT_GROUP_ENEMY} = require('../constants');
globalThis.MAP_OBJECT_GROUP_ENEMY = MAP_OBJECT_GROUP_ENEMY;

globalThis.MapObjectGroup = class MapObjectGroup {
};

// Overpulled enemies live on LiveSessionKillZone, not on the base KillZone, so isEnemyKilled() reaches them behind an
// instanceof guard - a plain-object pull can never satisfy it.
globalThis.LiveSessionKillZone = class LiveSessionKillZone {
    constructor(enemies, overpulledEnemies) {
        this.enemies = enemies;
        this.overpulledEnemies = overpulledEnemies;
    }

    getOverpulledEnemies() {
        return this.overpulledEnemies;
    }
};

const {KillZoneMapObjectGroup} = require('./killzonemapobjectgroup');

/**
 * A group wired up just enough to answer hasKilledAllRequiredEnemies(): the enemies that exist on the map, the
 * kill zones that kill some of them, and the route's teeming setting.
 *
 * @param {Object[]} enemies
 * @param {Number[]} killedEnemyIds
 * @param {Boolean} teeming
 * @returns {KillZoneMapObjectGroup}
 */
function buildGroup(enemies, killedEnemyIds, teeming) {
    // Object.create bypasses the MapObjectGroup constructor chain, which wants a real map manager.
    const group = Object.create(KillZoneMapObjectGroup.prototype);

    group.objects = killedEnemyIds.length === 0 ? [] : [{enemies: killedEnemyIds, overpulledEnemies: []}];
    group.manager = {
        getByName: (name) => {
            if (name !== MAP_OBJECT_GROUP_ENEMY) {
                throw new Error(`Unexpected map object group requested: ${name}`);
            }

            return {objects: enemies};
        },
    };

    globalThis.getState = () => ({getMapContext: () => ({getTeeming: () => teeming})});

    return group;
}

const requiredEnemy = (id, teeming = null) => ({id, required: true, teeming});

describe('KillZoneMapObjectGroup.hasKilledAllRequiredEnemies', () => {
    it('returns true when there are no required enemies at all', () => {
        const group = buildGroup([{id: 1, required: false, teeming: null}], [], false);

        expect(group.hasKilledAllRequiredEnemies()).toBe(true);
    });

    it('returns false when a required enemy is not in any pull', () => {
        const group = buildGroup([requiredEnemy(1)], [], false);

        expect(group.hasKilledAllRequiredEnemies()).toBe(false);
    });

    it('returns true when every required enemy is in a pull', () => {
        const group = buildGroup([requiredEnemy(1), requiredEnemy(2)], [1, 2], false);

        expect(group.hasKilledAllRequiredEnemies()).toBe(true);
    });

    it('counts an overpulled enemy as killed', () => {
        const group = buildGroup([requiredEnemy(1)], [], false);
        group.objects = [new globalThis.LiveSessionKillZone([], [1])];

        expect(group.hasKilledAllRequiredEnemies()).toBe(true);
    });

    it('ignores a teeming-only required enemy on a non-teeming route', () => {
        const group = buildGroup([requiredEnemy(1, 'visible')], [], false);

        expect(group.hasKilledAllRequiredEnemies()).toBe(true);
    });

    it('demands a teeming-only required enemy on a teeming route', () => {
        const group = buildGroup([requiredEnemy(1, 'visible')], [], true);

        expect(group.hasKilledAllRequiredEnemies()).toBe(false);
    });

    // The regression: 'hidden' is the real column value - the old code looked for 'invisible', so this enemy was
    // demanded of a teeming route even though it does not spawn on one.
    it('ignores a teeming-hidden required enemy on a teeming route', () => {
        const group = buildGroup([requiredEnemy(1, 'hidden')], [], true);

        expect(group.hasKilledAllRequiredEnemies()).toBe(true);
    });

    it('demands a teeming-hidden required enemy on a non-teeming route', () => {
        const group = buildGroup([requiredEnemy(1, 'hidden')], [], false);

        expect(group.hasKilledAllRequiredEnemies()).toBe(false);
    });
});
