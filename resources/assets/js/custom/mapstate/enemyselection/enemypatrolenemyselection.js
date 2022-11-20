class EnemyPatrolEnemySelection extends EnemySelection {
    constructor(map, sourceMapObject) {
        super(map, sourceMapObject);
    }

    getName() {
        return 'EnemyPatrolEnemySelection';
    }

    /**
     * Filters an enemy if it should be selected or not.
     * @param source {MapObject}
     * @param enemyCandidate {Enemy}
     * @returns {boolean}
     * @protected
     */
    _filter(source, enemyCandidate) {
        console.assert(this instanceof EnemyPatrolEnemySelection, 'this is not a EnemyPatrolEnemySelection', this);
        console.assert(source instanceof EnemyPatrol, 'source is not an EnemyPatrol', source);
        console.assert(enemyCandidate instanceof Enemy, 'enemyCandidate is not an Enemy', enemyCandidate);

        return true;
    }

    /**
     * The way the icon looks when an enemy may be selected.
     * @protected
     */
    _getLayerIcon() {
        console.assert(this instanceof EnemyPatrolEnemySelection, 'this is not a EnemyPatrolEnemySelection', this);
        return LeafletKillZoneIconEditMode;
    }
}
