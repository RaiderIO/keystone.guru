class FloorUnionMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, [MAP_OBJECT_GROUP_FLOOR_UNION], editable);

        this.fa_class = 'fa-vector-square';
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        return getState().getMapContext().getFloorUnions();
    }

    /**
     * @inheritDoc
     */
    _createLayer(remoteMapObject) {
        let layer = new LeafletIconMarker();
        layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
        return layer;
    }

    _createMapObject(layer, options = {}) {
        console.assert(this instanceof FloorUnionMapObjectGroup, 'this is not an FloorUnionMapObjectGroup', this);

        return new FloorUnion(this.manager.map, layer);
    }
}
