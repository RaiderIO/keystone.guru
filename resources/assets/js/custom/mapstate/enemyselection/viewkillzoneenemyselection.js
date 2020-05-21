class ViewKillZoneEnemySelection extends EnemySelection {
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
    _filter(source, enemyCandidate){
        console.assert(this instanceof ViewKillZoneEnemySelection, 'this is not a ViewKillZoneEnemySelection', this);
        console.assert(source instanceof KillZone, 'source is not a KillZone', source);
        console.assert(enemyCandidate instanceof Enemy, 'enemyCandidate is not an Enemy', enemyCandidate);
        return false;
    }

    /**
     * The way the icon looks when an enemy may be selected.
     * @protected
     */
    _getLayerIcon(){
        console.assert(this instanceof ViewKillZoneEnemySelection, 'this is not a ViewKillZoneEnemySelection', this);
        return LeafletKillZoneIconEditMode;
    }
}