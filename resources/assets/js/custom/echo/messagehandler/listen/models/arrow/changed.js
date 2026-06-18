class ArrowChangedHandler extends ModelChangedHandler {

    constructor(echo) {
        super(echo, ArrowChangedMessage.getName());
    }

    /**
     *
     * @param e {ArrowChangedMessage}
     * @return boolean
     */
    onReceive(e) {
        let shouldHandle = super.onReceive(e);

        console.log(`ArrowChangedHandler::onReceive:`, shouldHandle, e);
        if (shouldHandle) {
            let arrowMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ARROW);

            // Apply the correct coordinates for our choice of facade
            /** @type {MessageCoordinate[]} */
            let coordinates = this._getCorrectLatLngFromEvent(e);
            if (coordinates !== false) {
                if (coordinates.length > 0) {
                    e.model.floor_id = coordinates[0].floor_id;
                }
                e.model.polyline.vertices_json = JSON.stringify(coordinates);
            }

            let mapObject = arrowMapObjectGroup.loadMapObject(e.model, null, e.user);
            arrowMapObjectGroup.setMapObjectVisibility(mapObject, mapObject.shouldBeVisible());
        }

        return shouldHandle;
    }
}
