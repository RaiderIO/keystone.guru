class EnemyForcesCheckpointMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_ENEMY_FORCES_CHECKPOINT, editable);

        this.fa_class = 'fa-percent';
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        return getState().getMapContext().getEnemyForcesCheckpoints();
    }

    /**
     * @inheritDoc
     */
    _createLayer(remoteMapObject) {
        console.assert(this instanceof EnemyForcesCheckpointMapObjectGroup, 'this is not an EnemyForcesCheckpointMapObjectGroup', this);

        let layer = new LeafletEnemyForcesCheckpointMarker();
        layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));

        return layer;
    }

    /**
     * @inheritDoc
     */
    _createMapObject(layer, options = {}) {
        console.assert(this instanceof EnemyForcesCheckpointMapObjectGroup, 'this is not an EnemyForcesCheckpointMapObjectGroup', this);

        if (getState().isMapAdmin()) {
            return new AdminEnemyForcesCheckpoint(this.manager.map, layer);
        } else {
            return new EnemyForcesCheckpoint(this.manager.map, layer);
        }
    }
}
