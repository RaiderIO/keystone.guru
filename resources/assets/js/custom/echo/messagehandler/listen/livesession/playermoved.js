class PlayerMovedHandler extends MessageHandler {

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
     */
    onReceive(e) {
        super.onReceive(e);

        this._updatePlayerPosition(e.player_guid, e.character_name, e.lat, e.lng, e.floor_id);
    }
}
