class UserMouseLocationMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, [MAP_OBJECT_GROUP_USER_MOUSE_LOCATION], editable);

        this.title = 'Hide/show mouse locations of other users';
        this.fa_class = 'fa-mouse-pointer';
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        // The objects will be populated by the Echo server
        return [];
    }

    /**
     * @inheritDoc
     */
    _createLayer(remoteMapObject) {
        let layer = new LeafletIconUserMousePositionMarker();
        layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
        return layer;
    }

    _createMapObject(layer, options = {}) {
        console.assert(this instanceof MapIconMapObjectGroup, 'this is not an MapIconMapObjectGroup', this);

        return new UserMousePosition(this.manager.map, layer);
    }
}