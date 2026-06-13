class PlayerPositionMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, [MAP_OBJECT_GROUP_PLAYER_POSITION], editable);

        /** @type {Object.<string, PlayerPosition>} */
        this._playerPositionsByGuid = {};

        console.log(`player position map object group!`);
    }

    /**
     * @inheritDoc
     */
    _getRawObjects() {
        return getState().getMapContext().getPlayerPositions();
    }

    /**
     * @inheritDoc
     */
    _createLayer(remoteMapObject) {
        let layer = new LeafletIconPlayerPositionMarker();
        layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
        return layer;
        // return new LeafletIconPlayerPositionMarker(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
    }

    /**
     * @inheritDoc
     */
    _createMapObject(layer, options = {}) {
        console.assert(this instanceof PlayerPositionMapObjectGroup, 'this is not a PlayerPositionMapObjectGroup', this);

        return new PlayerPosition(this.manager.map, layer);
    }

    /**
     * @returns {boolean}
     */
    isUserToggleable() {
        return false;
    }

    /**
     * @param playerGuid {string}
     * @param characterName {string}
     * @param lat {Number}
     * @param lng {Number}
     * @param floorId {Number}
     */
    createOrUpdatePlayerPosition(playerGuid, characterName, lat, lng, floorId) {
        console.assert(this instanceof PlayerPositionMapObjectGroup, 'this is not a PlayerPositionMapObjectGroup', this);

        let obj = this._playerPositionsByGuid[playerGuid] ?? null;
        if (obj !== null) {
            obj.setPosition(lat, lng, floorId);
        } else {
            obj = this.loadMapObject({
                id: playerGuid,
                character_name: characterName,
                lat: lat,
                lng: lng,
                floor_id: floorId,
            });
            this._playerPositionsByGuid[playerGuid] = obj;
        }

        this.setMapObjectVisibility(obj, obj.shouldBeVisible());
    }
}
