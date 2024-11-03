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

            let mapObject = mapIconMapObjectGroup.loadMapObject(e.model, null, e.user);
            mapIconMapObjectGroup.setMapObjectVisibility(mapObject, true);
        }

        return shouldHandle;
    }
}
