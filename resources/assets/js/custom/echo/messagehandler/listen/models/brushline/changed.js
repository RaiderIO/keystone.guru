class BrushlineChangedHandler extends ModelChangedHandler {

    constructor(echo) {
        super(echo, BrushlineChangedMessage.getName());
    }

    /**
     *
     * @param e {BrushlineChangedMessage}
     * @return boolean
     */
    onReceive(e) {
        let shouldHandle = super.onReceive(e);

        console.log(`BrushlineChangedHandler::onReceive:`, shouldHandle, e);
        if (shouldHandle) {
            let brushlineMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_BRUSHLINE);

            // Apply the correct coordinates for our choice of facade
            /** @type {MessageCoordinate[]} */
            let coordinates = this._getCorrectLatLngFromEvent(e);
            if (coordinates !== false) {
                if (coordinates.length > 0) {
                    e.model.floor_id = coordinates[0].floor_id;
                }
                e.model.polyline.vertices_json = JSON.stringify(coordinates);
            }


            let mapObject = brushlineMapObjectGroup.loadMapObject(e.model, null, e.user);
            brushlineMapObjectGroup.setMapObjectVisibility(mapObject, mapObject.shouldBeVisible());
        }

        return shouldHandle;
    }
}
