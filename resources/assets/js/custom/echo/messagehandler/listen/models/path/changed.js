class PathChangedHandler extends ModelChangedHandler {

    constructor(echo) {
        super(echo, PathChangedMessage.getName());
    }

    /**
     *
     * @param e {PathChangedMessage}
     * @return boolean
     */
    onReceive(e) {
        let shouldHandle = super.onReceive(e);

        console.log(`PathChangedHandler::onReceive:`, shouldHandle, e);
        if (shouldHandle) {
            let pathMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_PATH);

            // Apply the correct coordinates for our choice of facade
            /** @type {MessageCoordinate[]} */
            let coordinates = this._getCorrectLatLngFromEvent(e);
            if (coordinates !== false) {
                if (coordinates.length > 0) {
                    e.model.floor_id = coordinates[0].floor_id;
                }
                e.model.polyline.vertices_json = JSON.stringify(coordinates);
            }


            let mapObject = pathMapObjectGroup.loadMapObject(e.model, null, e.user);
            pathMapObjectGroup.setMapObjectVisibility(mapObject, mapObject.shouldBeVisible());
        }

        return shouldHandle;
    }
}
