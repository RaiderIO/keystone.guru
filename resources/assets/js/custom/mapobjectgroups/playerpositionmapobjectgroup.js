class PlayerPositionMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, [MAP_OBJECT_GROUP_PLAYER_POSITION], editable);

        /** @type {Object.<string, PlayerPosition>} */
        this._playerPositionsByGuid = {};
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
     *
     * @param playerGuid
     * @returns {PlayerPosition|null}
     * @private
     */
    _getPlayerPositionByGuid(playerGuid) {
        // Try to fill the cache if it's not found
        if (!this._playerPositionsByGuid[playerGuid]) {
            for (let key in this.objects) {
                let playerPosition = this.objects[key];
                if (playerPosition.player_guid === playerGuid) {
                    this._playerPositionsByGuid[playerGuid] = playerPosition;
                    break;
                }
            }
        }

        return this._playerPositionsByGuid[playerGuid] ?? null;
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
     * @param classId {Number|null}
     * @param specializationId {Number|null}
     * @param specializationIconUrl {String|null}
     */
    createOrUpdatePlayerPosition(playerGuid, characterName, lat, lng, floorId, classId = null, specializationId = null, specializationIconUrl = null) {
        console.assert(this instanceof PlayerPositionMapObjectGroup, 'this is not a PlayerPositionMapObjectGroup', this);

        let obj = this._getPlayerPositionByGuid(playerGuid);
        if (obj !== null) {
            obj.setClassSpecialization(classId, specializationId, specializationIconUrl);
            obj.moveTo(lat, lng, floorId);
        } else {
            /** @type {PlayerPosition} */
            obj = this.loadMapObject({
                id: playerGuid,
                character_name: characterName,
                lat: lat,
                lng: lng,
                floor_id: floorId,
                class_id: classId,
                specialization_id: specializationId,
                specialization_icon_url: specializationIconUrl,
            });
            this._playerPositionsByGuid[playerGuid] = obj;
        }

        this.setMapObjectVisibility(obj, obj.shouldBeVisible());
    }
}
