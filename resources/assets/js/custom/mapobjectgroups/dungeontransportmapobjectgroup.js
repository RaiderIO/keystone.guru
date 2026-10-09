class DungeonTransportMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_DUNGEON_TRANSPORT, editable);

        this.fa_class = 'fa-ship';
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        return getState().getMapContext().getDungeonTransports();
    }

    /**
     * @inheritDoc
     */
    _createLayer(remoteMapObject) {
        console.assert(this instanceof DungeonTransportMapObjectGroup, 'this is not a DungeonTransportMapObjectGroup', this);
        let layer = new LeafletIconMarker();
        layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
        return layer;
    }

    /**
     * @inheritDoc
     */
    _createMapObject(layer, options = {}) {
        console.assert(this instanceof DungeonTransportMapObjectGroup, 'this is not a DungeonTransportMapObjectGroup', this);

        if (getState().isMapAdmin()) {
            return new AdminDungeonTransport(this.manager.map, layer);
        } else {
            return new DungeonTransport(this.manager.map, layer);
        }
    }
}
