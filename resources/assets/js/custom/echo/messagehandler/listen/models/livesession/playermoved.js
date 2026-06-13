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
     * @private
     */
    _updatePlayerPosition(playerGuid, characterName, lat, lng, floorId) {
        /** @type {PlayerPositionMapObjectGroup|boolean|MapObjectGroup} */
        let playerPositionMapObjectGroup = this.echo.map.mapObjectGroupManager
            .getByName(MAP_OBJECT_GROUP_PLAYER_POSITION);

        if (!(playerPositionMapObjectGroup instanceof PlayerPositionMapObjectGroup)) {
            return;
        }

        playerPositionMapObjectGroup.createOrUpdatePlayerPosition(playerGuid, characterName, lat, lng, floorId);
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
                this._updatePlayerPosition(e.player_guid, e.character_name, coordinates.lat, coordinates.lng, coordinates.floor_id);
            }
        }

        return shouldHandle;
    }
}
