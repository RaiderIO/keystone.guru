class MDTEnemySelection extends EnemySelection {
    constructor(map, sourceMapObject) {
        super(map, sourceMapObject);
    }

    getName() {
        return 'MDTEnemySelection';
    }

    /**
     * Filters an enemy if it should be selected or not.
     * @param source MapObject
     * @param enemyCandidate Enemy
     * @returns {boolean}
     * @protected
     */
    _filter(source, enemyCandidate) {
        console.assert(source instanceof Enemy, 'source is not an Enemy', source);
        console.assert(source.is_mdt, 'source not an MDT Enemy', source);
        console.assert(enemyCandidate instanceof Enemy, 'enemyCandidate is not an Enemy', enemyCandidate);

        return !enemyCandidate.is_mdt && enemyCandidate.npc_id === source.npc_id;
    }

    /**
     * The way the icon looks when an enemy may be selected.
     * @protected
     */
    _getLayerIcon() {
        return MDTEnemyIconSelected;
    }
}