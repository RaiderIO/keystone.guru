class UserMousePositionMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, [MAP_OBJECT_GROUP_USER_MOUSE_POSITION], editable);

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
        console.assert(this instanceof UserMousePositionMapObjectGroup, 'this is not a UserMousePositionMapObjectGroup', this);

        return new UserMousePosition(this.manager.map, layer);
    }

    /**
     *
     * @returns {boolean}
     */
    isUserToggleable() {
        return false;
    }

    /**
     * Creates a new user mouse location map object.
     *
     * @param echoUser {EchoUser}
     * @returns {UserMousePosition}
     */
    createNewUserMousePosition(echoUser) {
        console.assert(this instanceof UserMousePositionMapObjectGroup, 'this is not a UserMousePositionMapObjectGroup', this);

        let userMousePosition = this.loadMapObject({
            id: echoUser.getPublicKey(),
            public_key: echoUser.getPublicKey(),
            initials: echoUser.getInitials(),
            color: echoUser.getColor(),
            avatar_url: echoUser.getAvatarUrl(), // May be null if not set
            lat: -125,
            lng: 200,
            index: _.size(this.objects) + 1,
            local: true
        });

        this.signal('usermouseposition:new', {newUserMousePosition: userMousePosition});
        return userMousePosition;
    }
}
