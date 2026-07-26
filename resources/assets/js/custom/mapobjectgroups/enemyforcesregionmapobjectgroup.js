class EnemyForcesRegionMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_ENEMY_FORCES_REGION, editable);

        this.fa_class = 'fa-percent';
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        return getState().getMapContext().getEnemyForcesRegions();
    }

    /**
     * @inheritDoc
     */
    _createLayer(remoteMapObject) {
        console.assert(this instanceof EnemyForcesRegionMapObjectGroup, 'this is not an EnemyForcesRegionMapObjectGroup', this);

        let layer = new LeafletEnemyForcesRegionMarker();
        layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));

        return layer;
    }

    /**
     * @inheritDoc
     */
    _createMapObject(layer, options = {}) {
        console.assert(this instanceof EnemyForcesRegionMapObjectGroup, 'this is not an EnemyForcesRegionMapObjectGroup', this);

        if (getState().isMapAdmin()) {
            return new AdminEnemyForcesRegion(this.manager.map, layer);
        } else {
            return new EnemyForcesRegion(this.manager.map, layer);
        }
    }
}
