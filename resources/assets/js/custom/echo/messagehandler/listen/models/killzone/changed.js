class KillZoneChangedHandler extends ModelChangedHandler {

    constructor(echo) {
        super(echo, KillZoneChangedMessage.getName());

    }


    /**
     *
     * @param e {KillZoneChangedMessage}
     * @return boolean
     */
    onReceive(e) {
        let shouldHandle = super.onReceive(e);

        console.log(`KillZoneChangedHandler::onReceive:`, shouldHandle, e);
        if (shouldHandle) {
            let killZoneMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);

            // Apply the correct coordinates for our choice of facade
            let coordinates = this._getCorrectLatLngFromEvent(e, false);
            if( coordinates !== false ){
                e.model = $.extend({}, e.model, coordinates);
            }

            let mapObject = killZoneMapObjectGroup.loadMapObject(e.model, null, e.user);
            killZoneMapObjectGroup.setMapObjectVisibility(mapObject, true);

            this._showChangedFromEchoNotification(mapObject, e.user);
        }

        return shouldHandle;
    }
}
