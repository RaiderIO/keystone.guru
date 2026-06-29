class PlayerMovedHandler extends ModelChangedHandler {

    constructor(echo) {
        super(echo, PlayerMovedMessage.getName());
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
     * @private
     */
    _updatePlayerPosition(playerGuid, characterName, lat, lng, floorId, classId, specializationId, specializationIconUrl) {
        /** @type {PlayerPositionMapObjectGroup|boolean|MapObjectGroup} */
        let playerPositionMapObjectGroup = this.echo.map.mapObjectGroupManager
            .getByName(MAP_OBJECT_GROUP_PLAYER_POSITION);

        if (!(playerPositionMapObjectGroup instanceof PlayerPositionMapObjectGroup)) {
            return;
        }

        playerPositionMapObjectGroup.createOrUpdatePlayerPosition(
            playerGuid, characterName, lat, lng, floorId, classId, specializationId, specializationIconUrl
        );
    }

    _shouldHandleEchoEvent() {
        return true;
    }

    /**
     * @param e {PlayerMovedMessage}
     * @return boolean
     */
    onReceive(e) {
        let shouldHandle = super.onReceive(e);

        if (shouldHandle) {
            let coordinates = this._getCorrectLatLngFromEvent(e);
            if (coordinates !== false) {
                this._updatePlayerPosition(
                    e.player_guid,
                    e.character_name,
                    coordinates.lat,
                    coordinates.lng,
                    coordinates.floor_id,
                    e.class_id ?? null,
                    e.specialization_id ?? null,
                    e.specialization_icon_url ?? null
                );
            }
        }

        return shouldHandle;
    }
}
