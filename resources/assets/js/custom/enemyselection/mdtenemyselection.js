class MDTEnemySelection extends EnemySelection {
    constructor(map, sourceMapObject) {
        super(map, sourceMapObject);
    }

    /**
     * Filters an enemy if it should be selected or not.
     * @param source MapObject
     * @param enemyCandidate Enemy
     * @returns {boolean}
     * @protected
     */
    _filter(source, enemyCandidate) {
        console.assert(source instanceof Enemy, source, 'source is not an Enemy');
        console.assert(source.is_mdt, source, 'source not an MDT Enemy');
        console.assert(enemyCandidate instanceof Enemy, enemyCandidate, 'enemyCandidate is not an Enemy');

        return !enemyCandidate.is_mdt &&
            enemyCandidate.npc_id === source.npc_id;
    }

    /**
     * The way the icon looks when an enemy may be selected.
     * @protected
     */
    _getLayerIcon() {
        return MDTEnemyIconSelected;
    }
}