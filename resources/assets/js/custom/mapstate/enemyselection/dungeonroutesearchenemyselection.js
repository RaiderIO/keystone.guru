class DungeonRouteSearchEnemySelection extends EnemySelection {
    constructor(map, sourceMapObject) {
        super(map, sourceMapObject);
    }

    getName() {
        return 'DungeonRouteSearchEnemySelection';
    }

    /**
     * Filters an enemy if it should be selected or not.
     * @param {Enemy} source
     * @param {Enemy} enemyCandidate
     * @returns {boolean}
     * @protected
     */
    _filter(source, enemyCandidate) {
        console.assert(enemyCandidate instanceof Enemy, 'enemyCandidate is not an Enemy', enemyCandidate);

        return true;
    }

    /**
     * The way the icon looks when an enemy may be selected.
     * @protected
     */
    _getLayerIcon() {
        return null;
    }
}
