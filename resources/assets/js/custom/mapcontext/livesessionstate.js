/**
 * @typedef {Object} LiveSessionStatePlayer
 * @property {String} guid
 * @property {String} name
 * @property {Number|null} class_id
 * @property {Number|null} specialization_id
 * @property {String|null} specialization_icon_url
 * @property {Array<Number>} split_floors [lat, lng, floor_id]
 * @property {Array<Number>} facade [lat, lng, floor_id]
 */

/**
 * @typedef {Object} LiveSessionStateData
 * @property {Number[]} killed Sorted enemy ids, delta-encoded
 * @property {Number[]} in_combat Sorted enemy ids, delta-encoded
 * @property {Number[]} obsolete Sorted enemy ids, delta-encoded
 * @property {Array<Array<Number>>} overpulled [enemy_id, kill_zone_id] pairs
 * @property {LiveSessionStatePlayer[]} players
 */

/**
 * The complete live state of a live session, as the map context carries it on page load and every
 * `livesession-state` broadcast carries it afterwards.
 */
class LiveSessionState {
    /**
     * @param data {LiveSessionStateData|null|undefined}
     */
    constructor(data) {
        data = data ?? {};

        /** @type {Set<Number>} */
        this.killedEnemyIds = new Set(LiveSessionState.decodeEnemyIds(data.killed));
        /** @type {Set<Number>} */
        this.inCombatEnemyIds = new Set(LiveSessionState.decodeEnemyIds(data.in_combat));
        /** @type {Set<Number>} */
        this.obsoleteEnemyIds = new Set(LiveSessionState.decodeEnemyIds(data.obsolete));
        /** @type {Map<Number, Number>} */
        this.overpulledKillZoneIdByEnemyId = new Map((data.overpulled ?? []).map(pair => [pair[0], pair[1]]));
        /** @type {LiveSessionStatePlayer[]} */
        this.players = data.players ?? [];
    }

    /**
     * @param enemyId {Number}
     * @returns {Number|null}
     */
    getOverpulledKillZoneId(enemyId) {
        return this.overpulledKillZoneIdByEnemyId.get(enemyId) ?? null;
    }

    /**
     * @param player {LiveSessionStatePlayer}
     * @param useFacade {Boolean}
     * @returns {{lat: Number, lng: Number, floor_id: Number}}
     */
    static getPlayerCoordinates(player, useFacade) {
        let coordinates = useFacade ? player.facade : player.split_floors;

        return {lat: coordinates[0], lng: coordinates[1], floor_id: coordinates[2]};
    }

    /**
     * @param encodedEnemyIds {Number[]|null|undefined} The first id, followed by the difference to each previous id
     * @returns {Number[]}
     */
    static decodeEnemyIds(encodedEnemyIds) {
        let result = [];
        let previous = 0;

        for (let delta of encodedEnemyIds ?? []) {
            previous += delta;
            result.push(previous);
        }

        return result;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        LiveSessionState,
    };
}
