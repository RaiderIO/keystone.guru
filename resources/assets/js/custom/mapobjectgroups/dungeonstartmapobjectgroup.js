class DungeonStartMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_DUNGEON_START, editable);

        this.fa_class = 'fa-flag-checkered';
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        return getState().getMapContext().getDungeonStarts();
    }

    /**
     * @inheritDoc
     */
    _createLayer(remoteMapObject) {
        console.assert(this instanceof DungeonStartMapObjectGroup, 'this is not a DungeonStartMapObjectGroup', this);
        let layer = new LeafletDungeonStartMarker();
        layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
        return layer;
    }

    /**
     * @inheritDoc
     */
    _createMapObject(layer, options = {}) {
        console.assert(this instanceof DungeonStartMapObjectGroup, 'this is not a DungeonStartMapObjectGroup', this);

        if (getState().isMapAdmin()) {
            return new AdminDungeonStart(this.manager.map, layer);
        } else {
            return new DungeonStart(this.manager.map, layer);
        }
    }
}
