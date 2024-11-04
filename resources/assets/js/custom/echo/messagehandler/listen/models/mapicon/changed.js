class MapIconChangedHandler extends ModelChangedHandler {

    constructor(echo) {
        super(echo, MapIconChangedMessage.getName());
    }

    /**
     *
     * @param e {MapIconChangedMessage}
     * @return boolean
     */
    onReceive(e) {
        let shouldHandle = super.onReceive(e);

        console.log(`MapIconChangedHandler::onReceive:`, shouldHandle, e);
        if (shouldHandle) {
            let mapIconMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_MAPICON);

            // Apply the correct coordinates for our choice of facade
            let coordinates = this._getCorrectLatLngFromEvent(e);

            e.model = $.extend({}, e.model, coordinates);

            let mapObject = mapIconMapObjectGroup.loadMapObject(e.model, null, e.user);
            mapIconMapObjectGroup.setMapObjectVisibility(mapObject, mapObject.shouldBeVisible());
        }

        return shouldHandle;
    }
}
