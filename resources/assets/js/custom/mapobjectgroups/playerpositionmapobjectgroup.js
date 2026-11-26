class PlayerPositionMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, [MAP_OBJECT_GROUP_PLAYER_POSITION], editable);

        /** @type {Object.<string, PlayerPosition>} */
        this._playerPositionsByGuid = {};
        /** @type {Object.<string, String>} The last position and class/spec applied per player */
        this._lastAppliedByGuid = {};
    }

    /**
     * Players are added from the live session state once the map has loaded, see {@see LiveSessionStateManager}.
     * @inheritDoc
     */
    _getRawObjects() {
        return [];
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
        let applied = JSON.stringify([lat, lng, floorId, classId, specializationId, specializationIconUrl]);
        if (obj !== null) {
            // Every state carries every player; one that has not moved must not restart its glide
            if (this._lastAppliedByGuid[playerGuid] === applied) {
                return;
            }

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
        this._lastAppliedByGuid[playerGuid] = applied;

        this.setMapObjectVisibility(obj, obj.shouldBeVisible());
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        PlayerPositionMapObjectGroup,
    };
}
