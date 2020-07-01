class KillZoneEnemySelection extends EnemySelection {


    constructor(map, sourceMapObject) {
        super(map, sourceMapObject);

        this.sourceMapObject.register('object:deleted', this, this._onSourceMapObjectDeleted.bind(this));
    }

    /**
     * Filters an enemy if it should be selected or not.
     * @param source MapObject
     * @param enemyCandidate Enemy
     * @returns {boolean}
     * @protected
     */
    _filter(source, enemyCandidate){
        console.assert(this instanceof KillZoneEnemySelection, 'this is not a KillZoneEnemySelection', this);
        console.assert(source instanceof KillZone, 'source is not a KillZone', source);
        console.assert(enemyCandidate instanceof Enemy, 'enemyCandidate is not an Enemy', enemyCandidate);
        return true; //enemyCandidate.getKillZone() === null || enemyCandidate.getKillZone().id === source.id;
    }

    /**
     * The way the icon looks when an enemy may be selected.
     * @protected
     */
    _getLayerIcon(){
        console.assert(this instanceof KillZoneEnemySelection, 'this is not a KillZoneEnemySelection', this);
        return LeafletKillZoneIconEditMode;
    }

    /**
     * Called when the source map object was deleted by the user, while currently selecting enemies.
     * @private
     */
    _onSourceMapObjectDeleted() {
        console.assert(this instanceof KillZoneEnemySelection, 'this is not a KillZoneEnemySelection', this);

        this.map.setMapState(null);
    }


    cleanup() {
        super.cleanup();

        this.sourceMapObject.unregister('object:deleted', this);
    }
}